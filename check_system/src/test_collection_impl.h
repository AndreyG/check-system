#pragma once

#include "test.h"

#include <vector>

namespace check_system
{
    template<class Input, class Output>
    struct test_collection_impl : test_collection<Input, Output>
    {
        using typename test_collection<Input, Output>::test_ptr;

        void add_test(test_ptr test) override
        {
            tests_.push_back(std::move(test));
        }

        size_t tests_num() const { return tests_.size(); }

        test_t<Input, Output> const & get_test(size_t i) const { return *tests_[i]; }

    private:
        std::vector<test_ptr> tests_;
    };
}
