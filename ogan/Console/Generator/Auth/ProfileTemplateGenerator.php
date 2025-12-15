<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👤 PROFILE TEMPLATE GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class ProfileTemplateGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $userDir = $projectRoot . '/templates/user';
        $this->ensureDirectory($userDir);

        $templates = [
            'profile.ogan' => 'getProfileTemplate',
            'edit.ogan' => 'getEditTemplate',
        ];

        foreach ($templates as $filename => $method) {
            $path = $userDir . '/' . $filename;
            if (!$this->fileExists($path) || $force) {
                $this->writeFile($path, $this->$method());
                $generated[] = "templates/user/{$filename}";
            } else {
                $skipped[] = "templates/user/{$filename} (existe déjà)";
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getProfileTemplate(): string
    {
        return <<<'OGAN'
{{ extend('dashboard/layout.ogan') }}

{{ start('content') }}
<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
	<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
		<div>
			<h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
				Informations personnelles
			</h3>
			<p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
				Détails de votre compte utilisateur.
			</p>
		</div>
		<a href="{{ route('user_profile_edit') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
			Modifier
		</a>
	</div>
	<div class="border-t border-gray-200 dark:border-gray-700">
		<dl>
			<div class="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
				<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
					Nom complet
				</dt>
				<dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
					{{ user.name }}
				</dd>
			</div>
			<div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
				<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
					Adresse email
				</dt>
				<dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
					{{ user.email }}
				</dd>
			</div>
			<div class="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
				<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
					Compte créé le
				</dt>
				<dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
					{{ user.getCreatedAt() }}
				</dd>
			</div>
		</dl>
	</div>
</div>
{{ end }}
OGAN;
    }

    private function getEditTemplate(): string
    {
        return <<<'OGAN'
{{ extend('dashboard/layout.ogan') }}

{{ start('content') }}
<div class="max-w-2xl mx-auto">
	<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
		<div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
			<div class="flex items-center justify-between">
				<div>
					<h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
						Modifier mon profil
					</h3>
					<p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
						Mettez à jour vos informations personnelles.
					</p>
				</div>
				<a href="{{ route('user_profile') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
					← Retour
				</a>
			</div>
		</div>
		<div class="px-4 py-5 sm:p-6">
			{% form.render() %}
		</div>
	</div>

	<div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
		<div class="flex">
			<div class="flex-shrink-0">
				<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
					<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
				</svg>
			</div>
			<div class="ml-3">
				<h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
					Changement de mot de passe
				</h3>
				<p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
					Pour changer votre mot de passe, remplissez les trois champs de mot de passe. 
					Laissez-les vides pour conserver votre mot de passe actuel.
				</p>
			</div>
		</div>
	</div>
</div>
{{ end }}
OGAN;
    }
}
