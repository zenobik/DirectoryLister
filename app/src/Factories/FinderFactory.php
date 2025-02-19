<?php

namespace App\Factories;

use App\SortMethods;
use App\Support\Glob;
use Closure;
use DI\Container;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tightenco\Collect\Support\Collection;

class FinderFactory
{
    /** @const Application paths to be hidden */
    protected const APP_FILES = ['app', 'index.php', '.hidden'];

    /** @const Array of sort options mapped to their respective methods  */
    public const SORT_METHODS = [
        'accessed' => SortMethods\Accessed::class,
        'changed' => SortMethods\Changed::class,
        'modified' => SortMethods\Modified::class,
        'name' => SortMethods\Name::class,
        'natural' => SortMethods\Natural::class,
        'type' => SortMethods\Type::class,
    ];

    /** @var Container The application container */
    protected $container;

    /**
     * Create a new FinderFactory object.
     *
     * @param \DI\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Initialize and return the Finder component.
     *
     * @return Finder
     */
    public function __invoke(): Finder
    {
        $finder = Finder::create()->followLinks();
        $finder->ignoreVCS($this->container->get('hide_vcs_files'));

        if ($this->hiddenFiles()->isNotEmpty()) {
            $finder->filter(function (SplFileInfo $file): bool {
                return (bool) ! preg_match(Glob::toRegex(
                    sprintf('%s/{%s}', $this->container->get('base_path'), $this->hiddenFiles()->implode(','))
                ), $file->getRealPath());
            });
        }

        $sortOrder = $this->container->get('sort_order');
        if ($sortOrder instanceof Closure) {
            $finder->sort($sortOrder);
        } else {
            if (! array_key_exists($sortOrder, self::SORT_METHODS)) {
                throw new RuntimeException("Invalid sort option '{$sortOrder}'");
            }

            $this->container->call(self::SORT_METHODS[$sortOrder], [$finder]);
        }

        if ($this->container->get('reverse_sort')) {
            $finder->reverseSorting();
        }

        return $finder;
    }

    /**
     * Get a collection of hidden file paths.
     *
     * @return \Tightenco\Collect\Support\Collection
     */
    protected function hiddenFiles(): Collection
    {
        return Collection::make(
            $this->container->get('hidden_files')
        )->when($this->container->get('hide_app_files'), static function (Collection $collection) {
            return $collection->merge(self::APP_FILES);
        })->unique();
    }
}
