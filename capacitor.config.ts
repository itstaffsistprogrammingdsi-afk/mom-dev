import type { CapacitorConfig } from '@capacitor/cli';

const serverUrl = process.env.CAPACITOR_SERVER_URL ?? '';

const config: CapacitorConfig = {
    appId: 'id.mom.notulensi',
    appName: 'MoM',
    webDir: 'mobile/www',
    server: serverUrl
        ? {
              url: serverUrl,
              cleartext: serverUrl.startsWith('http://'),
              androidScheme: 'https',
          }
        : undefined,
    android: {
        allowMixedContent: false,
    },
    plugins: {
        SplashScreen: {
            launchShowDuration: 700,
            launchAutoHide: true,
            backgroundColor: '#f7f2ea',
            showSpinner: false,
        },
        StatusBar: {
            style: 'DEFAULT',
            backgroundColor: '#f7f2ea',
        },
    },
};

export default config;
