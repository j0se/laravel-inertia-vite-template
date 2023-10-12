<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class exportNewPermissions extends Command
{
  /**
  * The name and signature of the console command.
  *
  * @var string
  */
  protected $signature = 'app:export-new-permissions';

  /**
  * The console command description.
  *
  * @var string
  */
  protected $description = 'Create a seeder with custom permissions';

  /**
  * Execute the console command.
  *
  * @return int
  */
  public function handle()
  {

      $builtinPermissions = $this->getBuiltinPermissions();

      $permissions = Permission::get();

      $permissionsCustom = array();

      foreach ($permissions as $permission)
      {
          if (!in_array($permission->name, $builtinPermissions)) {

              $permissionsCustom[] = array(
                  'name' => $permission->name,
                  'guard_name' => $permission->guard_name,
              );
          }
      }
      $this->createFileSeeder($permissionsCustom);
      return 0;
  }

  protected function createFileSeeder($contents): void
  {
    $seederName = 'CustomPermissionSeeder';
    $seederDirectory = database_path('seeders');
    $seederFileName = $seederName . '.php';

    $output = '';

    foreach ($contents as $content) {
        $output .= "Permission::create([\n";
        $output .= "\t\t\t\t'name' => '" . $content['name'] . "', \n";
        $output .= "\t\t\t\t'guard_name' => '" . $content['guard_name'] . "', \n";
        $output .="\t\t\t]);\n\n\t\t\t";
    }
    $output = rtrim($output);
    $seederContent = <<<SEEDER
    <?php

    namespace Database\Seeders;

    use App\Models\Permission;
    use Illuminate\Database\Console\Seeds\WithoutModelEvents;
    use Illuminate\Database\Seeder;

    class $seederName extends Seeder
    {
        public function run()
        {
        \t\t$output
        }
    }
    SEEDER;

    if (!file_exists($seederDirectory)) {
      mkdir($seederDirectory, 0755, true);
    }

    $seederFilePath = $seederDirectory . '/' . $seederFileName;
    if (file_exists($seederFilePath)) {
        unlink($seederFilePath);
    }
    file_put_contents($seederFilePath, $seederContent);

  }

    /**
     * @return array|string[]
     */
    public function getBuiltinPermissions(): array
    {
        $builtinPermissions = array();
        collect(['user', 'permission', 'role', 'menu'])->each(function ($name) use (&$builtinPermissions) {
            collect(['create', 'read', 'update', 'delete'])->each(function ($ability) use ($name, &$builtinPermissions) {
                $builtinPermissions[] = sprintf('%s %s', $ability, $name);
            });
        });

        $extraPermission = ['read activities', 'read login activities'];
        return  array_merge($builtinPermissions, $extraPermission);

    }
}
