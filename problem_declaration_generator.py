#!/usr/bin/python

import sys

assert len(sys.argv) == 2

problem_name = sys.argv[1]

content = [\
    "#include \"", problem_name, "/problem_declaration.h\"\n\n", \
    "#include \"test.h\"\n\n", \
    "namespace check_system\n", \
    "{\n", \
    "    using namespace ", problem_name, ";\n\n", \
    "    struct environment;\n\n", \
    "    void create_tests(test_collection<input_t, output_t> &, environment &);\n", \
    "}"
    ]

with open('check_system/src/problem_declaration.h', 'w') as f:
    f.writelines(content)    
