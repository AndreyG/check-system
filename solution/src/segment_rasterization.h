#pragma once

#include "cg/segment.h"
#include "turn.h"

#include <boost/tuple/tuple.hpp>

namespace cg
{
   namespace details
   {
      template <class OutIter>
         bool raster_point(point_2 const & a, point_2 const & b, OutIter out)
      {
         if (a != b)
            return false;

         *out++ = a;
         return true;
      }

      template <class OutIter>
         bool raster_aa_line(point_2 a, point_2 const & b, OutIter out)
      {
         if (a.x != b.x && a.y != b.y)
            return false;

         size_t const idx = (a.x == b.x ? 1 : 0);
         for (int step = (a[idx] < b[idx] ? 1 : -1); (a[idx] - b[idx]) * step <= 0; a[idx] += step)
            *out++ = a;

         return true;
      }

      inline boost::tuple<point_2, point_2, point_2, point_2> fill_traits(segment_2 const & s)
      {
         if (s.p.x < s.q.x)
            if (s.p.y < s.q.y)
               return boost::make_tuple(point_2(1, 1), point_2(0, 1), point_2(1, 0), point_2(1, 1));
            else
               return boost::make_tuple(point_2(1, 0), point_2(1, 0), point_2(0, -1), point_2(1, 0));
         else
            if (s.p.y < s.q.y)
               return boost::make_tuple(point_2(0, 1), point_2(-1, 0), point_2(0, 1), point_2(0, 1));
            else
               return boost::make_tuple(point_2(0, 0), point_2(0, -1), point_2(-1, 0), point_2(-1, -1));
      }
   }

   template <class OutIter>
      void rasterize(segment_2 const & s, OutIter out)
   {
      point_2 current = floor(s.p);
      point_2 finish  = floor(s.q);

      if (  details::raster_point(current, finish, out)
         || details::raster_aa_line(current, finish, out))
         return;

      point_2 check, right, left, collinear;
      boost::tie(check, right, left, collinear) = details::fill_traits(s);          

      do
      {
         *out++ = current;

         switch (turn(s.p, s.q, check + current))
         {
         case tt_left:        current += left;        break;
         case tt_right:       current += right;       break;
         case tt_collinear:   current += collinear;   break;
         }
      }
      while (current != finish);

      *out++ = finish;
   }
}
