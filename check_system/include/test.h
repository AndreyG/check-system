#pragma once

#include <chrono>
#include <string>
#include <memory>

namespace check_system
{
    template<class Input, class Output>
    struct test_t
    {
        typedef std::chrono::milliseconds time_limit_t;

        virtual std::string     name()       const = 0;
        virtual Input           input()      const = 0;
        virtual time_limit_t    time_limit() const = 0;

        virtual bool is_correct(Input const &, Output const &) const = 0;

        virtual ~test_t() {}
    };

    template<class Input, class Output>
    struct test_collection
    {
        typedef std::unique_ptr<test_t<Input, Output>> test_ptr;

        virtual void add_test(test_ptr test) = 0;

        virtual ~test_collection() {}
    };
}
