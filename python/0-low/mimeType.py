import sys

debug_default = True


def d(msg: str, debug: bool = debug_default):
    if debug:
        print('>> ' + msg, file=sys.stderr)

mimeTypes = {}

n = int(input())  # Number of elements which make up the association table.
q = int(input())  # Number Q of file names to be analyzed.
for i in range(n):
    # ext: file extension
    # mt: MIME type.
    ext, mt = input().split()
    mimeTypes[ext.lower()] = mt

d(str(mimeTypes))

for i in range(q):
    fname = input()  # One file name per line.
    d(fname)
    try:
        ext = fname.rsplit('.', maxsplit=1)[1]
    except IndexError:
        d(fname + ' -> no ext')
        print("UNKNOWN")
        continue

    try:
        d(fname + ' -> ' + ext)
        ext = ext.lower()
        d(fname + ' -> ' + ext)
        d(fname + ' -> mimeType = ' + mimeTypes[ext])
        print(mimeTypes[ext])
    except KeyError:
        d(fname + ' -> mimeType = UNKNOWN')
        print("UNKNOWN")
