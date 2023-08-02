#!/usr/bin/env python
import os
import sys

project_dir = os.path.abspath(os.curdir)
image = 'attlaz/php:8.1'
image_debug = 'attlaz/php:8.1-debug'
arg = sys.argv
arg.pop(0)
arg = ' '.join(arg)

command = f"docker run --rm -it --init -v {project_dir}:/var/attlaz -w /var/attlaz {image} composer {arg}"

os.system(command)
