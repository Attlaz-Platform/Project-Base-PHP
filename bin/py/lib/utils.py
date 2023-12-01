import json
import os
import sys


def get_docker_image():
    composer_path = f"{get_project_dir()}/composer.json"
    with open(composer_path, 'r') as f:
        data = json.load(f)
    php_version = data['require']['php']
    match php_version:
        case '8.1' | '^8.1' | '>=8.1':
            return 'attlaz/php:8.1'
        case '8.2' | '^8.2' | '>=8.2':
            return 'attlaz/php:8.2'
        case '8.3' | '^8.3' | '>=8.3':
            return 'attlaz/php:8.3'
        case _:
            print(f'Unable to detect PHP version based on composer.json. (found version constraint `{php_version}`)')
            # Show error that we cannot define PHP version based on composer file
            return 'attlaz/php:8.1'


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
    docker_command = f"docker run --rm -it --init -v {project_dir}:/var/attlaz -w /var/attlaz {image} {command}"
    os.system(docker_command)
