#!/usr/bin/env python
from lib.utils import docker_run, get_arguments

arg = get_arguments()

command = f"bin/console {arg}"
docker_run(command)



#!/usr/bin/env python
# import os
# import sys
#
# project_dir = os.path.abspath(os.curdir)
# image = 'attlaz/php:8.1'
# image_debug = 'attlaz/php:8.1-debug'
# arg = sys.argv
# arg.pop(0)
# arg = ' '.join(arg)
# profile = True
#
# if profile:
#     command = f"docker run --rm -it --init -v {project_dir}:/var/attlaz -w /var/attlaz {image_debug} -d xdebug.scream=true -d xdebug.output_dir=/var/attlaz/profile -d xdebug.mode=profile -d xdebug.use_compression=0 -d xdebug.profiler_output_name=cachegrind.out.%%t bin/console {arg}"
# else:
#     command = f"docker run --rm -it --init -v {project_dir}:/var/attlaz -w /var/attlaz {image} bin/console {arg}"
#
# os.system(command)
