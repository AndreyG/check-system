#!/usr/bin/python

import sys

assert len(sys.argv) == 2

problem_name = sys.argv[1]

content = [\
    "#include \"", problem_name, "/problem_declaration.h\"\n\n", \
    "namespace check_system\n", \
    "{\n", \
    "    using namespace ", problem_name, ";\n", \
    "}"
    ]

with open('check_system/src/problem_declaration.h', 'w') as f:
    f.writelines(content)    
