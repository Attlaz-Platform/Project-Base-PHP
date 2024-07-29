import json
import os
import sys


def get_docker_image():
    composer_path = f"{get_project_dir()}/composer.json"
    with open(composer_path, 'r') as f:
        data = json.load(f)
    php_version = data['require']['php']
    match php_version:
        case '7.2' | '^7.2' | '>=7.2':
            return 'attlaz/php:7.2'
        case '7.4' | '^7.4' | '>=7.4':
            return 'attlaz/php:7.4'
        case '8.0' | '^8.0' | '>=8.0':
            return 'attlaz/php:8.0'
        case '8.1' | '^8.1' | '>=8.1':
            return 'attlaz/php:8.1'
        case '8.2' | '^8.2' | '>=8.2':
            return 'attlaz/php:8.2'
        case '8.3' | '^8.3' | '>=8.3':
            return 'attlaz/php:8.3'
        case '8.4' | '^8.4' | '>=8.4':
                    return 'attlaz/php:8.4'
        case _:
            print(f'Unable to detect PHP version based on composer.json. (found version constraint `{php_version}`) using PHP 8.1')
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
    profile = False
    if profile:
        docker_command = f"docker run --rm -e PROFILE=1 -it --init -v {project_dir}/profile:/xdebug -v {project_dir}:/var/attlaz -w /var/attlaz {image} {command}"
    else:
        docker_command = f"docker run --rm -it --init -v {project_dir}:/var/attlaz -w /var/attlaz {image} {command}"
    print(f'{docker_command}')
    os.system(docker_command)
