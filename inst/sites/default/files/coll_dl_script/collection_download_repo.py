import argparse
import os
import re
import requests

args = argparse.ArgumentParser()
args.add_argument('--user', help='User name (for downloading restricted-access resources')
args.add_argument('--pswd', help='User password (for downloading restricted-access resources')
args.add_argument('--recursive', action='store_const', const=True, help='Enable recursive download of child resources')
args.add_argument('--maxDepth', default=-1, type=int, help='Maximum recursion depth (-1 means do not limit the recursion depth)')
args.add_argument('--flat', action='store_const', const=True, help='Do not create directory structure (download all resources to the `tagetDir`)')
args.add_argument('--batch', action='store_const', const=True, help='Do not ask for user input (e.g. for the user name and password)')
args.add_argument('--targetDir', default='.', help='Directory to store downloaded resources')
args.add_argument('--matchUrl', nargs='*', default=[], help='Explicit list of allowed resource URLs')
args.add_argument('--skipUrl', nargs='*', default=[], help='Explicit list of allowed resource URLs')
args.add_argument('url', nargs='+', help='Resource URLs to be downloaded')
args = args.parse_args()

def getFilename(url):
    locationProp = '<{ingest.location}>'
    filenameProp = '<{fileName}>'
    resp = requests.get(url + '/metadata', headers={'Accept': 'application/n-triples', '{metadataReadMode}': 'resource'})
    filename = None
    location = ''
    for l in resp.text.splitlines():
        l = l[(len(url) + 3):]
        if l.startswith(locationProp):
            location = l[(len(locationProp) + 2):-3]
        if l.startswith(filenameProp):
            filename = l[(len(filenameProp) + 2):-3]
    return (filename, location)

def getChildren(url):
    searchUrl = re.sub('/[0-9]+$', '/search', url)
    data = {
        'sql': 'SELECT id FROM relations WHERE property = ? AND target_id = ?',
        'sqlParam[0]': '{parent}',
        'sqlParam[1]': re.sub('^.*/', '', url)
    }
    resp = requests.post(searchUrl, data=data, headers={'Accept': 'application/n-triples', '{metadataReadMode}': 'resource'})
    children = []
    for l in resp.text.splitlines():
        if re.search(' <search://match> ', l):
            children.append(l[1:l.find('>')])
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
        elif args.recursive and (res['depth'] < args.maxDepth or args.maxDepth == -1):
            print('Going into %s %s' % (res['url'], dirname))
            if args.flat:
                path = res['path']
            else:
                path = os.path.join(res['path'], dirname)
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

while len(stack) > 0:
    res = stack.pop()
    if len(args.matchUrl) > 0 and res['url'] not in args.matchUrl:
        continue
    if len(args.skipUrl) > 0 and res['url'] in args.skipUrl:
        continue
    if args.maxDepth >= 0 and res['depth'] > args.maxDepth:
        continue
    stack += download(res, args)

