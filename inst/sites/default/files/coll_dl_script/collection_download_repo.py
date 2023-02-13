import argparse
import getpass
import json
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
args.add_argument('--overwrite', action='store_const', const=True, help='Should existing files and directories be overwritten?')
args.add_argument('--targetDir', default='.', help='Directory to store downloaded resources')
args.add_argument('--matchUrl', nargs='*', default=[], help='Explicit list of allowed resource URLs')
args.add_argument('--skipUrl', nargs='*', default=[], help='Explicit list of allowed resource URLs')
args.add_argument('url', nargs='*', help='Resource URLs to be downloaded', default=[{resourceUrl}])
args = args.parse_args()

repoSchemas = {}
def getSchema(resUrl):
    repoUrl = re.sub('[0-9]+$', '', resUrl)
    if repoUrl not in repoSchemas:
        resp = requests.get(repoUrl + '/describe', headers={'Accept': 'application/json'})
        if resp.status_code != 200:
            raise Exception("Couldn't read " + repoUrl + " repository configuration")
        repoSchemas[repoUrl] = json.loads(resp.text)['schema']
    return repoSchemas[repoUrl]

def resolveUrl(location):
    url = None
    while location:
        urltmp = location
        resp = requests.head(urltmp)
        location = resp.headers.get('location')
        if location is None and resp.status_code == 200:
            url = re.sub('/metadata$', '', urltmp)
    return url

def getMetadata(url, schema):
    params = {
        'resourceProperties[0]': schema['fileName'],
        'relativesProperties[0]': schema['parent']
    }
    resp = requests.get(url + '/metadata', params=params, headers={'Accept': 'application/n-triples', 'X-METADATA-READ-MODE': '1_0_0_0'})
    graph = Graph()
    graph.parse(data=resp.text, format='nt')
    filename = graph.value(URIRef(url), URIRef(schema['fileName']), None, default=None, any=True)
    children = set()
    for s, p, o in graph.triples((None, URIRef(schema['parent']), None)):
        children.add(str(s))
    return (filename, list(children))

def readInput(msg, private):
    if private:
        return getpass.getpass(msg)
        
    try:
        inp = raw_input(msg)
    except NameError:
        inp = input(msg)
    return inp

def download(res, args):
    dirname = res['path']
    schema = getSchema(res['url'])
    (filename, children) = getMetadata(res['url'], schema)
    resp = requests.get(res['url'], allow_redirects=False, stream=True, auth=args.auth)
    hasBinary = resp.headers.get('location') is None
    if filename is None and (hasBinary or res['depth'] > 0):
        print("No file name found  for " + res['url'] + "- using the resource id instead")
        filename = re.sub('^.*/', '', res['url'])

    toDwnld = []
    if (resp.status_code == 401 or resp.status_code == 403) and args.auth is None and not args.batch:
        args.auth = requests.auth.HTTPBasicAuth(
            readInput('A restricted access resource encountered, please provide a username: ', False), 
            readInput('and a password: ', True)
        )
        toDwnld = download(res, args)
    elif resp.status_code == 302 and not hasBinary:
        if filename and not args.flat:
            dirname = os.path.join(dirname, filename)
            if not os.path.exists(dirname):
                os.makedirs(dirname)
            elif not args.overwrite:
                print("Aborting - " + dirname + " already exists")
                return []
        if len(children) > 0 and (res['depth'] < args.maxDepth or args.maxDepth == -1):
            print('Adding ' + str(len(children)) + ' children of ' + res['url'] + ' to the download queue')
            toDwnld = [{'url': x, 'path': dirname, 'depth': res['depth'] + 1} for x in children]
    elif resp.status_code == 200 and hasBinary:
        path = os.path.join(dirname, filename)
        if not os.path.exists(path) or os.path.isdir(path) or args.overwrite:
            print('Downloading ' + res['url'] + ' as ' + path)
            with open(path, 'wb') as of:
                for chunk in resp.iter_content(chunk_size=8192):
                    if chunk:
                        of.write(chunk)
        else:
            print("Aborting - " + path + " already exists")
    else:
        sc = resp.status_code
        if sc == 401:
            sc = 'Access Denied'
        print("Failed to download " + res['url'] + " with code " + str(sc))

    return toDwnld

stack = []
for i in args.url:
    ir = resolveUrl(i)
    if ir:
        stack.append({'url': ir, 'path': args.targetDir, 'depth': 0})
        getSchema(ir)
    else:
        print("Can't resolve " + i)

args.auth = None
if args.user and args.pswd:
    args.auth = requests.auth.HTTPBasicAuth(args.user, args.pswd)
if not os.path.exists(args.targetDir):
    os.makedirs(args.targetDir)
    
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
print('Download ended')
