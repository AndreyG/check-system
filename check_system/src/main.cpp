#include "problem_declaration.h"

#include "reporter.h"
#include "test_collection_impl.h"
#include "runner.h"

#include "environment.h"

int main()
{
    using namespace check_system;

    environment env;
    env.resources_dir = "../problems/data";

    test_collection_impl<input_t, output_t> tc;

    create_tests(tc, env);

    reporter_t reporter;

    run_tests(tc, reporter);
}
