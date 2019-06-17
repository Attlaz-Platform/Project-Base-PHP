<?php
declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Project\App\Environment;
use Attlaz\Project\Command\Annotation\Command as CommandAnnotation;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Echron\Tools\FileSystem;

class CommandDiscovery
{

    /**
     * @var string
     */
    private $directory;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var CommandDefinition[]
     */
    private $commands = [];
    private $commandsLoaded = false;

    public function __construct(string $sourcePath)
    {
        $this->directory = $sourcePath;

        $this->annotationReader = new AnnotationReader();

        //TODO: this can be removed in Doctrine Annotations v2
        /** @noinspection PhpDeprecationInspection */
        AnnotationRegistry::registerLoader('class_exists');
    }

    /**
     * Returns all the commands
     * @return CommandDefinition[]
     */
    public function getCommands(): array
    {
        //TODO: cache commands
        if (!$this->commandsLoaded) {
            $this->discoverCommands();
            $this->commandsLoaded = true;
        }

        return $this->commands;
    }

    private function discoverCommands(): void
    {
        $commandDirectoryPath = Environment::getCommandDirectoryPath($this->directory);
        if (is_null($commandDirectoryPath)) {
            throw new \Exception('Unable to discover commands: command directory does not exist');
        }

        $commands = [];
        $files = FileSystem::listFiles($commandDirectoryPath, true);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $className = $this->getClassName($file);

                $commandDefinition = $this->registerCommand($className);
                if (!\is_null($commandDefinition)) {
                    if (isset($commands[$commandDefinition->task])) {
                        $strErrorMessage = 'Unable to register command: ';
                        $strErrorMessage .= 'There is already a command defined for task ';
                        $strErrorMessage .= '"' . $commandDefinition->task . '"';

                        throw new \Exception($strErrorMessage);
                    }
                    $commands[$commandDefinition->task] = $commandDefinition;
                }
            }
        }

        $this->commands = \array_values($commands);
    }

    private function getClassName(\SplFileInfo $file): string
    {
        $path = $file->getPath();
        $path = \str_replace($this->directory . Environment::SOURCE_LOCATION, '', $path);

        $class = \str_replace('/', '\\', $path) . '\\' . $file->getBasename('.php');

        $class = '\\' . \ltrim($class, '\\');

        return $class;
    }

    private function registerCommand(string $className): ?CommandDefinition
    {
        try {
            //TODO: if the className is actually a file, the file is included by calling "class_exists",
            //  putting "autoload" to false doesn't help and make the function returns false
            if (!class_exists($className, true)) {
                return null;
            }

            $reflectionClass = new \ReflectionClass($className);

            /** @var CommandAnnotation|null $annotation */
            $annotation = $this->annotationReader->getClassAnnotation($reflectionClass, CommandAnnotation::class);
            if (is_null($annotation)) {
                return null;
            }

            //Check if class extends AbstractCommand
            if (!$reflectionClass->isSubclassOf(AbstractCommand::class)) {
                $strErrorMessage = 'Unable to register command "' . $className . '": ';
                $strErrorMessage .= 'must extend "' . AbstractCommand::class . '" class';
                throw new \Exception($strErrorMessage);
            }
            //Check if invoke method exists
            if (!$reflectionClass->hasMethod(AbstractCommand::INVOKE_METHOD)) {
                $strErrorMessage = 'Unable to register command "' . $className . '": ';
                $strErrorMessage .= 'must have "' . AbstractCommand::INVOKE_METHOD . '" method';
                throw new \Exception($strErrorMessage);
            }
            $invokeMethodReflection = $reflectionClass->getMethod(AbstractCommand::INVOKE_METHOD);

            //TODO: add information about return type
            // $invokeMethodReflection->getReturnType()
            //TODO: make sure that we don't have duplicate task definitions

            $commandParameters = $this->getCommandParameters($invokeMethodReflection);

            $commandDefinition = new CommandDefinition();
            $commandDefinition->task = $annotation->getTask();
            $commandDefinition->className = $className;

            foreach ($commandParameters as $commandParameter) {
                $commandDefinition->addParameter($commandParameter);
            }

            return $commandDefinition;
        } catch (AnnotationException $ex) {
            $strErrorMessage = 'Unable to register command "' . $className . '":' . $ex->getMessage();
            throw new \Exception($strErrorMessage);
            //TODO: handle invalid/incomplete annotations,
            // maybe make it possible to validate the project before building it?
        }
    }

    private function getCommandParameters(\ReflectionMethod $invokeMethodReflection): array
    {
        $parameters = [];

        $reflectionParameters = $invokeMethodReflection->getParameters();
        /** @var \ReflectionParameter $parameter */
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameterName = $reflectionParameter->getName();

            $required = !$reflectionParameter->isOptional();
            $typeName = null;
            if ($reflectionParameter->hasType()) {
                //TODO: validate type and throw exception when it's an unknown type

                $type = $reflectionParameter->getType();
                if (!\is_null($type)) {
                    $typeName = $type->getName();
                }
            }
            $default = null;
            if ($reflectionParameter->isDefaultValueAvailable()) {
                $default = $reflectionParameter->getDefaultValue();
            }
            $parameterDefinition = new CommandParameterDefinition($parameterName, $typeName, $required, $default);
            $parameters[$parameterName] = $parameterDefinition;
        }

        return $parameters;
    }
}
