/***************************************************************************

    file                 : util.h
    created              : Wed May 29 22:20:44 CEST 2002
    copyright            : (C) 2001 by Eric Espi�
    email                : Eric.Espie@torcs.org
    version              : $Id$

 ***************************************************************************/

/***************************************************************************
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/
 
/** @file    
    		
    @author	<a href=mailto:torcs@free.fr>Eric Espie</a>
    @version	$Id$
*/

#ifndef _UTIL_H_
#define _UTIL_H_

#include <plib/ssg.h>

extern int GetFilename(const char *filename, const char *filepath, char *buf, const int BUFSIZE);
extern float getHOT(ssgRoot *root, float x, float y);

/* Use the texture name to select options like mipmap */
class ssgLoaderOptionsEx : public ssgLoaderOptions
{
 public:
    ssgLoaderOptionsEx()
	: ssgLoaderOptions() 
	{}

    virtual void makeModelPath ( char* path, const char *fname ) const
	{
	    ulFindFile ( path, model_dir, fname, NULL ) ;
	}
    
    virtual void makeTexturePath ( char* path, const char *fname ) const
	{
	    ulFindFile ( path, texture_dir, fname, NULL ) ;
	}

};

#endif /* _UTIL_H_ */ 



