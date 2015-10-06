#!/usr/bin/env python
import sys
import sets
import operator
from collections import deque

# max duration of entire project cycle; we ask this value of the user;
# use 60 as a default
DEADLINE = 60.0

# represents an edge in the arrow diagram graph
class Activity:
    nid = 1
    def __init__(self,name,duration):
        self.name = name
        self.duration = duration

        # list of activities that come before and after this one
        self.prevTasks = []
        self.nextTasks = []

        # assign unique ids for start and end nodes
        self.startNode = Activity.nid
        self.endNode = Activity.nid + 1

        # earliest start (ES), earliest finish (EF), latest start (LS)
        # latest finish (LF)
        self.es = 0.0
        self.ef = 0.0
        self.ls = 0.0
        self.lf = 0.0

        Activity.nid += 2

    def __repr__(self):
        s = "ACTIVITY " + self.name + "\n"
        s += "    ES: " + str(self.es) + "\n"
        s += "    EF: " + str(self.ef) + "\n"
        s += "    LS: " + str(self.ls) + "\n"
        s += "    LF: " + str(self.lf)
        return s

    # correct_verteces() - make vertex ids make sense in relation to
    # the prev/next activities for the activity (this is useful for
    # turning a set of activities into a DAG)
    def correct_verteces(self):
        # correct start node
        for a in self.prevTasks:
            a.endNode = self.startNode

        # correct end node
        for a in self.nextTasks:
            a.startNode = self.endNode

    # find_early_metrics() - compute the ES and EF stat for the
    # activity, along with its next activities; this should ideally be
    # called on a top-level activity (e.g. the one that is the first
    # task in the project)
    def find_early_metrics(self):
        # the algorithm here is surprisingly simple: use a depth-first
        # approach to compute the metrics for a given level of
        # activities in the activity graph; then we can compute the
        # next level since we have all the information required to
        # find the earliest start

        q = deque([self])
        while len(q) > 0:
            a = q.popleft()

            # ES - latest, earliest finish of prev tasks
            lef = 0.0
            for t in a.prevTasks:
                if t.ef > lef:
                    lef = t.ef
            a.es = lef

            # EF - earliest finish plus duration time
            a.ef = lef + a.duration

            # enqueue the activity's next tasks
            for t in a.nextTasks:
                q.append(t)

    # find_late_metrics() - same as find_early_metrics() but for LS
    # and LF; this method should be called on the final activity
    # within a project (e.g. one with no next tasks)
    def find_late_metrics(self,amt=DEADLINE):
        q = deque([self])
        while len(q) > 0:
            a = q.popleft()

            # LF - earliest, latest start of next tasks
            els = DEADLINE
            for t in a.nextTasks:
                if t.ls < els:
                    els = t.ls
            a.lf = els

            # LS - latest finish minus activity duration
            a.ls = els - a.duration

            # enqueue the activity's prev tasks
            for t in a.prevTasks:
                q.append(t)

    # critical_path() - computes the critical path by brute force;
    # this method returns a tuple containing (total_duration,path)
    def critical_path(self):
        def f(x):
            ans = x.critical_path()
            return (self.duration + ans[0],','.join((self.name,ans[1])))

        paths = map(f,self.nextTasks)
        if len(paths) == 0:
            return (self.duration,self.name)
        return max(paths,key=lambda x: x[0])

# read_activities() - read activity entries from text file
def read_activities(filename):
    global DEADLINE
    f = open(filename,'r')
    activities = []
    mapping = {}

    # first line is deadline duration
    line = f.readline()
    if line == "":
        sys.stderr.write("error: expected first line to contain deadline duration\n")
        exit(1)
    try:
        DEADLINE = float(line.strip())
    except ValueError:
        sys.stderr.write("error: bad deadline duration value on line 1\n")
        exit(1)

    # read activities from file with the following format:
    # ACTIVITY-NAME DURATION [PREV-ACTIVITY, ...]
    n = 1
    while True:
        line = f.readline()
        if line == "":
            break
        n += 1
        if line == "\n" or line == "\r\n":
            # skip blank lines
            continue

        parts = line.split()
        l = len(parts)
        if l < 2:
            sys.stderr.write("error: line " + int(n) + ": expected at least two items\n")
            exit(1)

        name = parts[0]
        try:
            duration = float(parts[1])
        except ValueError:
            sys.stderr.write("error: expected numeric duration for activity on line "+str(n)+"\n")
            exit(1)

        actv = Activity(name,duration)
        actv.pt = parts[2:]
        mapping[name] = actv
        activities.append(actv)

    # perform late bindings
    for a in activities:
        for t in a.pt:
            if not t in mapping:
                sys.stderr.write("error: reference " + t + " did not map to a defined activity\n")
                exit(1)
            b = mapping[t]
            b.nextTasks.append(a)
            a.prevTasks.append(mapping[t])
    for a in activities:
        a.correct_verteces()

    return activities

# arrow_info() - compute arrow info from specified file
def arrow_info(filename):
    activities = read_activities(filename)

    # find the top-level activity; there should only be one
    top = None
    for a in activities:
        if len(a.prevTasks) == 0:
            if not top is None:
                sys.stderr.write("error: more than one top-level activity found\n")
                exit(1)
            top = a

    top.find_early_metrics()

    # find the bottom-level activity (final activity); there should only be one
    bot = None
    for a in activities:
        if len(a.nextTasks) == 0:
            if not bot is None:
                sys.stderr.write("error: more than one bottom-level activity found\n")
                exit(1)
            bot = a

    bot.find_late_metrics()

    # sort by earliest start so that the activities are in order by precedence
    activities.sort(key=operator.attrgetter('es'))
    for a in activities:
        print a

    # print out critical path
    print "CRITICAL PATH", top.critical_path()

# input to program are the command-line arguments which specify the
# input files to process
for a in sys.argv[1:]:
    print "-"*80
    print "OUTPUT FOR FILE '"+a+"'"
    print "-"*80
    arrow_info(a)
    print "-"*80
