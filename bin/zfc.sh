#!/bin/sh

FILE_PATH="${BASH_SOURCE[0]}";

# resolve symlink
if ([ -h "${FILE_PATH}" ]) then
  while([ -h "${FILE_PATH}" ]) do
    cd $( dirname "$FILE_PATH" )
    FILE_PATH=$( readlink "$( basename $FILE_PATH )" )
  done
fi

# get root directory
ROOT_DIR="$( dirname $( cd "$( dirname "${FILE_PATH}" )" && pwd ) )"

# set up ZF Tool
ZF_CONFIG_FILE="$ROOT_DIR/.zf.ini"
export ZF_CONFIG_FILE

ZEND_TOOL_INCLUDE_PATH_PREPEND="$ROOT_DIR/library"
export ZEND_TOOL_INCLUDE_PATH_PREPEND

zf $@
