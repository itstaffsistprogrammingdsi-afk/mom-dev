import type { Config } from 'ziggy-js';

type RouteParameter = string | number | Record<string, string | number | undefined>;

declare global {
    function route(name?: string, params?: RouteParameter, absolute?: boolean): string;
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        route: typeof route;
    }
}

export type { Config };
