#pragma once

#include <memory>

#include "cg/segment.h"

namespace segment_rasterization
{
    struct output_collection
    {
        virtual void add(cg::point_2 const & cell) = 0;

        virtual ~output_collection() {}
    };

    struct input_t
    {
        cg::segment_2 seg;
        std::unique_ptr<output_collection> out;
    };

    struct output_t {};

    output_t solve(input_t const &);
}
