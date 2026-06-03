<?php

namespace App\Support;

use Illuminate\Http\Request;

class Breadcrumb
{
    /**
     * Mapping ruta → [sectiunea-sidebar, segmente-baza].
     *
     * Sectiunile sunt afisate ca label non-clickable (gen "Workspace",
     * "Observability") pentru ca nu au pagina-landing dedicata.
     * Segmentele de baza pot avea url null daca sunt pagina curenta.
     *
     * Rutele cu params/query (ex: processes.show + tab) sunt completate
     * dinamic in current() prin metodele withXxx().
     */
    private const ROUTE_MAP = [
        'dashboard'      => ['Workspace',     [['label' => 'Dashboard',   'url' => null]]],
        'metrics'        => ['Workspace',     [['label' => 'Metrics',     'url' => '/metrics']]],
        'processes'      => ['Workspace',     [['label' => 'Processes',   'url' => '/processes']]],
        'processes.show' => ['Workspace',     [['label' => 'Processes',   'url' => '/processes']]],
        'apache-logs'    => ['Observability', [['label' => 'Apache Logs', 'url' => null]]],
        'alerts'         => ['Observability', [['label' => 'Alerts',      'url' => null]]],
        'percentiles'    => ['Observability', [['label' => 'Percentiles', 'url' => null]]],
        'profile.edit'   => ['System',        [['label' => 'Profile',     'url' => null]]],
    ];

    private const METRICS_TAB_LABELS = [
        'cpu'     => 'CPU',
        'ram'     => 'RAM',
        'network' => 'Network',
        'disk'    => 'Disk',
    ];

    private const PROCESS_TAB_LABELS = [
        'cpu' => 'CPU',
        'ram' => 'RAM',
        'io'  => 'Disk I/O',
    ];

    /**
     * Calculeaza breadcrumb-ul curent pe baza request-ului.
     * Intoarce: ['section' => string, 'crumbs' => array<['label','url']>].
     */
    public static function current(?Request $request = null): array
    {
        $request ??= request();
        $routeName = $request->route()?->getName();

        if ($routeName === null || ! isset(self::ROUTE_MAP[$routeName])) {
            // Fallback prietenos: ruta nu e in mapping (gen pagina de auth / 404).
            return ['section' => 'Workspace', 'crumbs' => []];
        }

        [$section, $crumbs] = self::ROUTE_MAP[$routeName];

        $crumbs = match ($routeName) {
            'metrics'        => self::withMetricsTab($crumbs, $request),
            'processes.show' => self::withProcessSegments($crumbs, $request),
            default          => $crumbs,
        };

        // Ultimul crumb e pagina curenta — il fortam non-clickable indiferent
        // de url-ul din map (evita link-uri redundante la pagina pe care esti).
        if (! empty($crumbs)) {
            $crumbs[count($crumbs) - 1]['url'] = null;
        }

        return ['section' => $section, 'crumbs' => $crumbs];
    }

    private static function withMetricsTab(array $crumbs, Request $request): array
    {
        $tab = (string) $request->query('tab', 'cpu');
        if (! isset(self::METRICS_TAB_LABELS[$tab])) {
            return $crumbs;
        }
        $crumbs[] = ['label' => self::METRICS_TAB_LABELS[$tab], 'url' => null];
        return $crumbs;
    }

    private static function withProcessSegments(array $crumbs, Request $request): array
    {
        $name = $request->route('name');
        if (! is_string($name) || $name === '') {
            return $crumbs;
        }

        $crumbs[] = [
            'label' => $name,
            'url'   => route('processes.show', ['name' => $name]),
        ];

        $tab = (string) $request->query('tab', 'cpu');
        if (isset(self::PROCESS_TAB_LABELS[$tab])) {
            $crumbs[] = ['label' => self::PROCESS_TAB_LABELS[$tab], 'url' => null];
        }

        return $crumbs;
    }
}
