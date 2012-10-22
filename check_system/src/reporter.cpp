#include "reporter.h"

#include <boost/asio.hpp>

#include "port.h"
#include "message_type.h"

using namespace boost::asio::ip;    

namespace check_system
{
    struct reporter_t::impl_t
    {
        impl_t(unsigned short port)
            : acceptor_(io_service_, tcp::endpoint(tcp::v4(), port))
            , socket_(io_service_)
        {
            acceptor_.accept(socket_);
        }

        void send_type(message_type t) 
        {
            boost::system::error_code err;
            boost::asio::write(socket_, boost::asio::buffer(&t, sizeof(t)), err);
        }

        void send_string(std::string const & str)
        {
            const size_t str_len = str.size();

            boost::system::error_code err;
            boost::asio::write(socket_, boost::asio::buffer(&str_len, sizeof(size_t)), err);
            boost::asio::write(socket_, boost::asio::buffer(str), err);
        }

    private:
        boost::asio::io_service     io_service_;
        tcp::acceptor               acceptor_;
        tcp::socket                 socket_;
    };

    void reporter_t::start_test(std::string const & test_name)
    {
        impl->send_type(TEST_STARTED);
        impl->send_string(test_name);
    }

    void reporter_t::pass_test()
    {
        impl->send_type(TEST_PASSED);
    }

    void reporter_t::wa()
    {
        impl->send_type(WA);
    }

    void reporter_t::tle()
    {
        impl->send_type(TLE);
    }

    void reporter_t::accept_solution()
    {
        impl->send_type(ACCEPTED);
    }

    reporter_t::reporter_t()
        : impl(new impl_t(PORT))
    {}

    reporter_t::~reporter_t()
    {
        delete impl;
    }
}
