#include "segment_rasterization/problem_declaration.h"

#include "test.h"
#include "environment.h"

#include "cg/io.h"

#include <boost/filesystem.hpp>
#include <boost/filesystem/fstream.hpp>
#include <boost/range/algorithm/equal.hpp>

using namespace segment_rasterization;
using namespace check_system;

struct collection_over_vector : output_collection
{
    collection_over_vector(std::vector<cg::point_2> & v)
        : v_(v)
    {}

    void add(cg::point_2 const & cell) override
    {
        v_.push_back(cell);
    }

private:
    std::vector<cg::point_2> & v_;
};

struct test_from_data_file : test_t<input_t, output_t>
{
    test_from_data_file(std::istream & in, std::string const & filename)
        : filename_(filename)
    {
        in >> seg_;
        size_t cells_num;
        in >> cells_num;
        expected_.resize(cells_num);
        for (size_t i = 0; i != cells_num; ++i)
            in >> expected_[i];
    }

    time_limit_t time_limit()   const override { return std::chrono::milliseconds(2000); }
    std::string  name()         const override { return filename_; }

    input_t input() const override
    {
        input_t res;

        res.seg = seg_;
        gotten_.clear();
        res.out.reset(new collection_over_vector(gotten_));

        return res;
    }

    bool is_correct(input_t const &, output_t const &) const override
    {
        return boost::equal(expected_, gotten_);
    }

private:
    cg::segment_2 seg_;
    std::vector<cg::point_2>            expected_;
    mutable std::vector<cg::point_2>    gotten_;
    std::string filename_;
};

void add_tests_from_data_files(test_collection<input_t, output_t> & tc, boost::filesystem::path const & path)
{
    for (boost::filesystem::directory_iterator it(path), end; it != end; ++it)
    {
        auto path = it->path();
        auto filename = path.filename().string();

        boost::filesystem::ifstream file(path);

        tc.add_test(std::unique_ptr<test_t<input_t, output_t>>(new test_from_data_file(file, filename)));
    }
}

namespace check_system
{
    void create_tests(test_collection<input_t, output_t> & tc, environment & env)
    {
        add_tests_from_data_files(tc, env.resources_dir / "segment_rasterization");
    }
}
