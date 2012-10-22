#pragma once

#include <string>
#include <boost/scoped_ptr.hpp>

namespace check_system
{
    struct reporter_t
    {
        reporter_t();
        ~reporter_t();

        void start_test(std::string const & test_name);
        void pass_test();
        void wa();
        void tle();
        void accept_solution();

    private:
        struct impl_t;
        impl_t * impl;
    };
}
