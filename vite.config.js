import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { resolve } from "path";
import combine from "vite-plugin-combine";

export default defineConfig({
    css: {
        preprocessorOptions: {
            scss: {
                // additionalData: `$injectedColor: orange;`,
            },
        },
    },
    plugins: [
        laravel({
            input: [
                "resources/js/app.js",
                "resources/gull/assets/styles/sass/themes/lite-purple.scss",
                "resources/gull/assets/styles/sass/themes/lite-blue.scss",
                "resources/gull/assets/styles/sass/themes/dark-purple.scss",
                "resources/gull/assets/js/script.js",
            ],
            refresh: true,
            // detectTls: "gull-laravel11.test",
        }),
        // combine({
        //     input: [
        //         "resources/gull/assets/js/vendor/jquery-3.3.1.min.js",
        //         "resources/gull/assets/js/vendor/bootstrap.bundle.min.js",
        //         "resources/gull/assets/js/vendor/perfect-scrollbar.min.js",
        //     ],
        //     output: "public/assets/js/common-bundle-script.js",
        // }),
    ],
    build: {
        rollupOptions: {
            input: {
                app: resolve(__dirname, "resources/js/app.js"),
                litePurple: resolve(
                    __dirname,
                    "resources/gull/assets/styles/sass/themes/lite-purple.scss"
                ),
                liteBlue: resolve(
                    __dirname,
                    "resources/gull/assets/styles/sass/themes/lite-blue.scss"
                ),
                darkPurple: resolve(
                    __dirname,
                    "resources/gull/assets/styles/sass/themes/dark-purple.scss"
                ),
                script: resolve(
                    __dirname,
                    "resources/gull/assets/js/script.js"
                ),
            },
            output: {
                entryFileNames: (chunkInfo) => {
                    if (chunkInfo.name === "app") return "public/js/[name].js";
                    if (chunkInfo.name === "script")
                        return "public/assets/js/[name].js";
                    return "public/assets/styles/css/themes/[name].min.css";
                },
                chunkFileNames: "public/js/[name]-[hash].js",
                assetFileNames: "public/assets/[ext]/[name]-[hash].[ext]",
            },
        },
    },
});
