import argparse
import os
import pip
import re
import subprocess
import sys

lacking = []
try:
    import requests
except ModuleNotFoundError:
    lacking.append('requests')
try:
    from rdflib import Graph, URIRef
except ModuleNotFoundError:
    lacking.append('rdflib')

if len(lacking) > 0:
    choice = ''
    while choice != '1' and choice != '2':
        print("\nYour python installation lacks some dependencies (" + ", ".join(lacking) + ").")
        print("You can install them on your own or we can try to install them for you. which option do you prefer?\n")
        print("1. Install manually")
        print("2. Try to install automatically")
        choice = input().strip()
    print("\n")
    if choice == '1':
        for i in lacking:
            print("You are missing the '%s' library. You should be able to install it with `pip install %s` or from your operating system package (e.g. python3-%s under debian/ubuntu or python-%s under fedora/redhat/centos)\n" % (i, i, i, i))
    else:
        for i in lacking:
            subprocess.check_call([sys.executable, '-m', 'pip', 'install', i])
        print("\nInstallation successful - please run the script again")
        quit()

args = argparse.ArgumentParser()
args.add_argument('--user', help='User name (for downloading restricted-access resources')
args.add_argument('--pswd', help='User password (for downloading restricted-access resources')
args.add_argument('--maxDepth', default=-1, type=int, help='Maximum recursion depth (-1 means do not limit the recursion depth)')
args.add_argument('--flat', action='store_const', const=True, help='Do not create directory structure (download all resources to the `tagetDir`)')
args.add_argument('--batch', action='store_const', const=True, help='Do not ask for user input (e.g. for the user name and password)')
args.add_argument('--targetDir', default='.', help='Directory to store downloaded resources')
args.add_argument('--matchUrl', nargs='*', default=[], help='Explicit list of allowed resource URLs')
args.add_argument('--skipUrl', nargs='*', default=[], help='Explicit list of allowed resource URLs')
args.add_argument('url', nargs='*', help='Resource URLs to be downloaded', default=['{resourceUrl}'])
args = args.parse_args()

def getFilename(url):
    id = re.sub('^.*/', '', url)
    resp = requests.get(url + '/metadata', headers={'Accept': 'application/n-triples', '{metadataReadMode}': 'resource'})
    graph = Graph()
    graph.parse(data=resp.text, format='nt')
    location = graph.value(URIRef(url), URIRef('{ingest.location}'), None, default='repo_resource_' + id, any=True)
    filename = graph.value(URIRef(url), URIRef('{fileName}'), None, default=None, any=True)
    return (filename, os.path.basename(location))

def getChildren(url):
    searchUrl = re.sub('/[0-9]+$', '/search', url)
    data = {
        'sql': 'SELECT id FROM relations WHERE property = ? AND target_id = ?',
        'sqlParam[0]': '{parent}',
        'sqlParam[1]': re.sub('^.*/', '', url)
    }
    resp = requests.post(searchUrl, data=data, headers={'Accept': 'application/n-triples', '{metadataReadMode}': 'resource'})
    graph = Graph()
    graph.parse(data=resp.text, format='nt')
    children = []
    for s, p, o in graph.triples((None, URIRef('{searchMatch}'), None)):
        children.append(str(s))
    return children

def readInput(msg):
    try:
        inp = raw_input(msg)
    except NameError:
        inp = input(msg)
    return inp

def download(res, args):
    (filename, dirname) = getFilename(res['url'])
    req = requests.get(res['url'], allow_redirects=True, stream=True, auth=requests.auth.HTTPBasicAuth(args.user, args.pswd))

    toDwnld = []
    if req.status_code == 200 or req.status_code == 204:
        if filename is not None:
            path = os.path.join(res['path'], filename)
            print('Downloading %s as %s' % (res['url'], path))
            if not os.path.exists(res['path']):
                os.makedirs(res['path'])
            with open(os.path.join(res['path'], filename), 'wb') as of:
                for chunk in req.iter_content(chunk_size=8192):
                    if chunk:
                        of.write(chunk)
        else:
            os.makedirs(os.path.join(res['path'], dirname))
            if res['depth'] < args.maxDepth or args.maxDepth == -1:
                print('Going into %s %s' % (res['url'], dirname))
                if args.flat is None:
                    path = os.path.join(res['path'], dirname)
                else:
                    path = res['path']
                toDwnld = getChildren(res['url'])
                toDwnld = [{'url': x, 'path': path, 'depth': res['depth'] + 1} for x in toDwnld]
    elif req.status_code == 401 and args.user is None and not args.batch:
        # get login and password and try again
        args.user = readInput('A restricted access resource encountered, please provide a username: ')
        args.pswd = readInput('and a password: ')
        toDwnld = download(res, sparqlUrlTmpl, args)
    else:
        sc = req.status_code
        print('Failed to download %s with code %s' % (res['url'], str(sc) if sc != 401 else 'Access Denied'))

    return toDwnld

stack = []
for i in args.url:
    stack.append({'url': i, 'path': args.targetDir, 'depth': 0})

downloaded = set()
while len(stack) > 0:
    res = stack.pop()
    if res['url'] in downloaded:
        continue
    if len(args.matchUrl) > 0 and res['url'] not in args.matchUrl:
        continue
    if len(args.skipUrl) > 0 and res['url'] in args.skipUrl:
        continue
    if args.maxDepth >= 0 and res['depth'] > args.maxDepth:
        continue
    stack += download(res, args)
    downloaded.add(res['url'])

