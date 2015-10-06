#!/usr/bin/python
import sys
import re

FILE_NAME_REGEX = re.compile('(.*)\.(.*)')

# process_tasks_file() - take arrow.py .tasks file format and produce
# an ArrowDiagramGenerator .csv file for it; this involves renaming
# tasks to use numerical ids
def process_tasks_file(fullName,fname,ext):
    tasksFile = open(fullName)
    tasksFile.readline() # read off deadline duration line
    tasks = []
    assoc = {}

    n = 1
    while True:
        line = tasksFile.readline()
        if line == "":
            break
        if line == "\n" or line == "\r\n":
            # skip blank lines
            continue
        parts = line.split()
        l = len(parts)
        if l < 2:
            exit(1)
        name = parts[0]
        try:
            duration = float(parts[1])
        except ValueError:
            exit(1)
        prevTasks = parts[2:]
        task = (str(n),prevTasks,duration)
        assoc[name] = str(n)
        tasks.append(task)
        n += 1

    outputFile = open(fname + ".csv","w")
    outputFile.write("ID,Predecessors,Duration,Total Slack\n")
    for t in tasks:
        preds = ','.join(map(lambda x: assoc[x],t[1]))
        outputFile.write(t[0] + ",\"" + preds + "\"," + str(t[2]) + ",\n")

# process_dot_file() - take ArrowDiagramGenerator output (graphviz
# format) and convert all labels back to the tasks defined in the
# .tasks file; we just replace all instances of label="" with the
# desired label for each id; for simplicity we assume each label entry
# is on its own line
def process_dot_file(fullName,fname,ext):
    tasksFile = open(fname + ".tasks")
    tasksFile.readline() # read off deadline duration line
    assoc = {}

    n = 1
    while True:
        line = tasksFile.readline()
        if line == "":
            break
        if line == "\n" or line == "\r\n":
            # skip blank lines
            continue
        parts = line.split()
        l = len(parts)
        if l < 2:
            exit(1)
        name = parts[0]
        assoc[str(n)] = name
        n += 1

    dotdotfile = open(fullName)
    result = ""
    while True:
        line = dotdotfile.readline()
        if line == "":
            break

        m = re.search('label=([0-9]*)',line)
        if m and m.group(1) != "":
            lk = assoc[m.group(1)]
            line = re.sub('label=([0-9]*)','label=' + lk,line)
        result += line

    newfile = open(fname + ".new." + ext,"w")
    newfile.write(result)

for a in sys.argv[1:]:
    match = FILE_NAME_REGEX.match(a)
    if match:
        name, ext = match.group(1,2)
    else:
        sys.stderr.write("error: file name '" + a + "' was not understood\n")
        exit(1)

    if ext == 'tasks':
        process_tasks_file(a,name,ext)
    elif ext == 'dot':
        process_dot_file(a,name,ext)
