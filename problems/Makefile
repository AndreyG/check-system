all: problems

INCLUDE_DIR = -Iinclude
INCLUDE_DIR += -I../check_system/include
INCLUDE_DIR += -I../geom_primitives 

problems: segment_rasterization.o
	ar rs libproblems.a segment_rasterization.o

segment_rasterization.o: src/segment_rasterization.cpp 
	g++ -c -std=c++11 src/segment_rasterization.cpp $(INCLUDE_DIR)
