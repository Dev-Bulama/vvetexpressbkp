<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\ThemeColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ThemeColorRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return ThemeColor::class;
    }

    public static function DefaultColorUpdate($request)
    {
        self::query()->update([
            'is_default' => false,
        ]);

        $theme = self::find($request->selected_id);

        GeneraleSettingRepository::updateOrCreateThemeColor($theme->primary, $theme->secondary);

        self::changeStyleCSS($theme->primary, $theme->secondary);

        return self::update($theme, [
            'is_default' => true,
        ]);
    }

    public static function updateColorPalette(Request $request)
    {
        $colorVariants = json_decode($request->generated_color_variants);

        $themeColor = self::query()->where('is_default', true)->first();

        $updatedColors = [];

        foreach ($colorVariants as $key => $value) {
            $updatedColors['variant_'.$key] = $value;

            if ($key == 100) {
                $updatedColors['secondary'] = $value;
            }

            if ($key == 500) {
                $updatedColors['primary'] = $value;
            }
        }

        if (! $themeColor) {
            $themeColor = self::create([
                'primary' => $updatedColors['primary'],
                'secondary' => $updatedColors['secondary'],
                'is_default' => true,
            ]);
        }

        $themeColor->update($updatedColors);

        GeneraleSettingRepository::updateOrCreateThemeColor($updatedColors['primary'], $updatedColors['secondary']);

        self::changeStyleCSS($updatedColors['primary'],$updatedColors['secondary']);

        return $updatedColors;
    }

    public static function changeStyleCSS($primary ,$secondary){

        // update style.css
        self::replaceInCssFile(
            public_path('assets/css/style.css'),
            [
                '/\s*--theme-color:\s*(#[a-zA-Z0-9]{6});/' => '  --theme-color: '.$primary.';',
                '/\s*--theme-hover-bg:\s*(#[a-zA-Z0-9]{6});/' => '  --theme-hover-bg: '.$secondary.';',
            ]
        );

        // update login.css
        self::replaceInCssFile(
            public_path('assets/css/login.css'),
            [
                '/\s*--theme_color:\s*(#[a-zA-Z0-9]{6});/' => '  --theme_color: '.$primary.';',
            ]
        );

        // NOTE: The laravel-filemanager stylesheet (public/vendor/laravel-filemanager/css/lfm.css)
        // is intentionally NOT rewritten here. It is a git-tracked vendor file, so rewriting it at
        // runtime fails on servers where the web user cannot write it and conflicts with deployments.
        // Instead, the theme color is injected live from GeneraleSetting into the file manager page
        // (see resources/views/vendor/laravel-filemanager/index.blade.php), which works on every
        // environment without touching files on disk.
    }

    protected static function replaceInCssFile($file, array $patternReplacements)
    {
        if (! file_exists($file)) {
            Log::error("Theme CSS update failed: file does not exist at {$file}");

            return;
        }

        if (! is_writable($file)) {
            Log::error("Theme CSS update failed: file is not writable (check owner/permissions) at {$file}");

            return;
        }

        $str = file_get_contents($file);

        if ($str === false) {
            Log::error("Theme CSS update failed: could not read file at {$file}");

            return;
        }

        foreach ($patternReplacements as $pattern => $replacement) {
            $str = preg_replace($pattern, $replacement, $str);
        }

        if (file_put_contents($file, $str) === false) {
            Log::error("Theme CSS update failed: could not write file at {$file}");
        }
    }
}
