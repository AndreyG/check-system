#pragma once

#include <gmpxx.h>
#include <cg/point.h>
#include <limits>
#include <math.h>
#include <boost/numeric/interval.hpp>

namespace cg
{
   enum turn_t
   {
      tt_left = 1,
      tt_collinear = 0,
      tt_right = -1,
      tt_uncertain = -2
   };

   namespace details
   {
      inline turn_t gmp_turn(point_2 const & a, point_2 const & b, point_2 const & c)
      {
         mpq_class det = (mpq_class(b.x) - mpq_class(a.x)) * (mpq_class(c.y) - mpq_class(a.y))
                       - (mpq_class(b.y) - mpq_class(a.y)) * (mpq_class(c.x) - mpq_class(a.x));

         if (det < 0)
            return tt_right;
         if (det > 0)
            return tt_left;

         return tt_collinear;
      }

      inline turn_t interval_turn(point_2 const & a, point_2 const & b, point_2 const & c)
      {
         typedef boost::numeric::interval<double> ivt;

         ivt det = (ivt(b.x) - a.x) * (ivt(c.y) - a.y)
                 - (ivt(b.y) - a.y) * (ivt(c.x) - a.x);

         if (det.lower() > 0)
            return tt_left;
         if (det.upper() < 0)
            return tt_right;

         if (det.upper() == 0 && det.lower() == 0)
            return tt_collinear;

         return tt_uncertain;
      }

      inline turn_t fp_turn(point_2 const & a, point_2 const & b, point_2 const & c)
      {
         double pa = (b.x - a.x) * (c.y - a.y);
         double pb = (c.x - a.x) * (b.y - a.y);

         double cp = pa - pb;
         double eps = 4 * std::numeric_limits<double>::epsilon() * (fabs(pa) + fabs(pb));

         if (cp > eps)
            return tt_left;
         if (cp < -eps)
            return tt_right;

         return tt_uncertain;
      }
   }

   inline turn_t turn(point_2 const & a, point_2 const & b, point_2 const & c)
   {
      turn_t res = details::fp_turn(a, b, c);
      if (res != tt_uncertain)
         return res;

      res = details::interval_turn(a, b, c);
      if (res != tt_uncertain)
         return res;

      return details::gmp_turn(a, b, c);
   }
}
