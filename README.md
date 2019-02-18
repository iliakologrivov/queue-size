## Installation

You can install the package via composer:

```bash
composer require iliakologrivov/queue-size
```

### Publish

By running `php artisan vendor:publish --provider="Iliakologrivov\Queuesize\QueueSizeServiceProvider"` in your project all files for this package will be published.

## Usage

```
$ php artisan queue:size
+------------+------------+
| Queue name | Count jobs |
+------------+------------+
| default    | 9          |
+------------+------------+

$ php artisan queue:size --queue=q1 --queue=q2
+------------+------------+
| Queue name | Count jobs |
+------------+------------+
| q1         | 0          |
| q2         | 0          |
+------------+------------+

$ php artisan queue:size --failed-jobs
+------------+------------+-------------+
| Queue name | Count jobs | Failed Jobs |
+------------+------------+-------------+
| default    | 9          | 0           |
+------------+------------+-------------+

$ php artisan queue:size --preset=all
+------------+------------+-------------+
| Queue name | Count jobs | Failed Jobs |
+------------+------------+-------------+
| default    | 9          | 0           |
| q1         | 0          | 0           |
| q2         | 0          | 0           |
+------------+------------+-------------+
```