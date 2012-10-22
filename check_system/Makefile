all: checker printer

INCLUDE_DIR = -Iinclude
INCLUDE_DIR += -I../problems/include
INCLUDE_DIR += -I../geom_primitives 

LIBRARY_DIR = -L../problems -L../solution

LIBRARIES = -lproblems -lsolution
LIBRARIES += -lboost_system 
LIBRARIES += -lpthread 
LIBRARIES += -lboost_filesystem
LIBRARIES += -lgmp

checker: src/main.cpp src/reporter.cpp src/problem_declaration.h src/runner.h
	g++ -std=c++11 src/main.cpp src/reporter.cpp -o checker $(INCLUDE_DIR) $(LIBRARY_DIR) $(LIBRARIES) 

printer: src/printer.cpp
	g++ -std=c++11 src/printer.cpp -o printer -lboost_system -lpthread
