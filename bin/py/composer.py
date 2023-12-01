#!/usr/bin/env python
from lib.utils import docker_run, get_arguments

arg = get_arguments()

command = f"composer {arg}"
docker_run(command)
