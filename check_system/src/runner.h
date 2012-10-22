#pragma once

#include "test_collection_impl.h"

#include <future>

namespace check_system
{
    template<class Input, class Output>
    void run_tests(test_collection_impl<Input, Output> const & tc, reporter_t & reporter)
    {
        for (size_t i = 0; i != tc.tests_num(); ++i)
        {
            test_t<Input, Output> const & test = tc.get_test(i);

            reporter.start_test(test.name());

            auto input = test.input();
            auto output = std::async(std::launch::async, &solve, std::cref(input));

            if (output.wait_for(test.time_limit()) == std::future_status::ready)
            {
                if (test.is_correct(input, output.get()))
                {
                    reporter.pass_test();
                }
                else
                {
                    reporter.wa();
                    return;
                }
            }
            else
            {
                reporter.tle();
                std::abort();
            }
        }

        reporter.accept_solution();
    }
}
