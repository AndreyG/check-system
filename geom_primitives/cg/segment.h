#pragma once

#include "point.h"

namespace cg
{
   struct segment_2
   {
      segment_2() {}
      segment_2(point_2 const & p, point_2 const & q)
         : p(p)
         , q(q)
      {}

      point_2 p;
      point_2 q;
   };

   inline segment_2 & normalize_order(segment_2 & s)
   {
      if (s.p >= s.q)
         std::swap(s.p, s.q);

      return s;
   }
}
