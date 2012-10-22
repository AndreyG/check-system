#include <boost/asio.hpp>
#include <boost/lexical_cast.hpp>
#include <thread>

#include "port.h"
#include "message_type.h"

int main()
{
    using namespace boost::asio::ip;    
    using namespace check_system;

    boost::asio::io_service io_service;

    tcp::socket socket(io_service);
    tcp::resolver resolver(io_service);
    tcp::resolver::query query("localhost", boost::lexical_cast<std::string>(PORT)); 
    for (;;)
    {
        try 
        {
            boost::asio::connect(socket, resolver.resolve(query));
            break;
        }
        catch (...)
        {
            std::this_thread::sleep_for(std::chrono::seconds(1));
        }
    }

    for (;;)
    {
        message_type t;
        socket.read_some(boost::asio::buffer(&t, sizeof(message_type)));

        switch (t)
        {
        case TEST_STARTED:
            {
                size_t string_size;
                socket.read_some(boost::asio::buffer(&string_size, sizeof(size_t)));

                std::vector<char> buffer(string_size);
                socket.read_some(boost::asio::buffer(buffer));
                std::string test_name(buffer.begin(), buffer.end());
                std::cout << test_name << "...\t\t\t";
            }
            break;
        case TEST_PASSED:
            std::cout << "passed" << std::endl;
            break;
        case ACCEPTED:
            std::cout << "Accepted" << std::endl;
            return EXIT_SUCCESS;
        }
    }
}
