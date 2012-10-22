#pragma once

#include <math.h>
#include <ostream>

namespace cg
{
   struct point_2
   {
      point_2()
         : x(0)
         , y(0)
      {}

      point_2(double x, double y)
         : x(x)
         , y(y)
      {}

      point_2 & operator += (point_2 const & a);
      point_2 & operator -= (point_2 const & a);
      point_2 & operator *= (double m);

      double & operator [] (size_t idx)       { return idx ? y : x; } 
      double   operator [] (size_t idx) const { return idx ? y : x; } 

      double x;
      double y;
   };

   inline point_2 floor(point_2 const & a)
   {
      return point_2(::floor(a.x), ::floor(a.y));
   }

   inline bool operator < (point_2 const & a, point_2 const & b)
   {
      if (a.x < b.x)
         return true;

      if (a.x > b.x)
         return false;

      if (a.y < b.y)
         return true;

      return false;
   }

   inline bool operator > (point_2 const & a, point_2 const & b)
   {
      return b < a;
   }

   inline bool operator >= (point_2 const & a, point_2 const & b)
   {
      return !(a < b);
   }

   inline bool operator <= (point_2 const & a, point_2 const & b)
   {
      return b >= a;
   }

   inline bool operator == (point_2 const & a, point_2 const & b)
   {
      return a.x == b.x && a.y == b.y;
   }

   inline bool operator != (point_2 const & a, point_2 const & b)
   {
      return !(a == b);
   }

   inline point_2 & point_2::operator += (point_2 const & a)
   {
      x += a.x;
      y += a.y;

      return *this;
   }

   inline point_2 & point_2::operator -= (point_2 const & a)
   {
      x -= a.x;
      y -= a.y;

      return *this;
   }

   inline point_2 & point_2::operator *= (double m)
   {
      x *= m;
      y *= m;

      return *this;
   }

   inline point_2 operator -(point_2 const & p)
   {
      return point_2(-p.x, -p.y);
   }

   inline point_2 operator + (point_2 a, point_2 const & b)
   {
      return a += b;
   }

   inline point_2 operator - (point_2 a, point_2 const & b)
   {
      return a -= b;
   }

   inline point_2 operator * (point_2 a, double b)
   {
      return a *= b;
   }

   inline point_2 operator * (double b, point_2 a)
   {
      return a *= b;
   }

   inline double cross(point_2 const & a, point_2 const & b)
   {
      return a.x * b.y - a.y * b.x;
   }
}
