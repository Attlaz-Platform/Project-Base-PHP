import json
import os
import re
import subprocess
import sys


def get_docker_image():
    composer_path = f"{get_project_dir()}/composer.json"
    with open(composer_path, 'r') as f:
        data = json.load(f)
    php_version = data['require']['php']
    # Use the first major.minor from the constraint (any format: "8.4", "^8.4", ">=8.4",
    # "^8.4.0", "^v8.4", "8.4.*", ">=8.4 <9", ...). We don't verify the image exists here:
    # docker run pulls it on demand, and docker_run() reports a clear error if it doesn't exist.
    match = re.search(r'(\d+)\.(\d+)', php_version)
    if not match:
        sys.exit(f"Unable to detect a PHP version from the composer.json constraint '{php_version}'.")

    return f'attlaz/php:{match.group(1)}.{match.group(2)}'


def get_arguments():
    arg = sys.argv
    arg.pop(0)
    arg = ' '.join(arg)
    return arg


def get_project_dir():
    return os.path.abspath(os.curdir)


# image_debug = 'attlaz/php:8.1-debug'

def docker_run(command):
    project_dir = get_project_dir()
    image = get_docker_image()
    profile = False
    if profile:
        docker_command = f"docker run --rm -e PROFILE=1 -it --init -v {project_dir}/profile:/xdebug -v {project_dir}:/var/attlaz -w /var/attlaz {image} {command}"
    else:
        docker_command = f"docker run --rm -it --init -v {project_dir}:/var/attlaz -w /var/attlaz {image} {command}"
    print(f'{docker_command}')
    # Let docker pull the image on demand. A docker-level failure (image doesn't exist, pull
    # denied, bad invocation) exits 125 -- distinct from the wrapped command's own exit code --
    # so we can surface a clear hint without inspecting the image on every invocation.
    result = subprocess.run(docker_command, shell=True)
    if result.returncode == 125:
        print(
            f"\nUnable to run '{image}'. Docker reported an error (exit 125): the image may not "
            f"exist for the PHP version required in composer.json, or could not be pulled.",
            file=sys.stderr,
        )
    sys.exit(result.returncode)
