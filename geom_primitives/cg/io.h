#pragma once

#include <ostream>
#include <istream>

#include "point.h"
#include "segment.h"

namespace
{
    inline std::istream & skip_char(std::istream & in, char ch)
    {
        char c;
        while ((in >> c) && (c != ch));
        return in;
    }
}

namespace cg
{
    inline std::ostream& operator << (std::ostream & out, point_2 const & pt)
    {
        out << "(" << pt.x << ", " << pt.y << ")";
        return out;
    }

    inline std::ostream& operator << (std::ostream & out, segment_2 const & seg)
    {
        out << "[" << seg.p << " - " << seg.q << "]";
        return out;
    }

    inline std::istream& operator >> (std::istream & in, point_2 & pt)
    {
        return skip_char(skip_char(skip_char(in, '(') >> pt.x, ',') >> pt.y, ')');
    }

    inline std::istream& operator >> (std::istream & in, segment_2 & seg)
    {
        return skip_char(skip_char(skip_char(in, '[') >> seg.p, ',') >> seg.q, ']');
    }
}
