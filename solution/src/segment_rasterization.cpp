#include "segment_rasterization/problem_declaration.h"
#include "segment_rasterization.h"

namespace segment_rasterization
{
    struct out_iterator
    {
        out_iterator & operator ++ (int) { return *this; }
        out_iterator & operator * ()     { return *this; }

        out_iterator & operator = (cg::point_2 const & pt) 
        { 
            out_->add(pt);
            return *this; 
        }

        explicit out_iterator(output_collection & out)
            : out_(&out)
        {}

    private:
        output_collection * out_;
    };

    output_t solve(input_t const & input)
    {
        out_iterator out_iter(*input.out);
        rasterize(input.seg, out_iter);
        return output_t();
    }
}
