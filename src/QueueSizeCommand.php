<?php

namespace Iliakologrivov\Queuesize;

use Illuminate\Console\Command;

class QueueSizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:size
                            {--queue=*}
                            {--delay=0}
                            {--failed-jobs}
                            {--p|preset=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View queue size';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $params = $this->getParams();

        $headers = ['Queue name', 'Count jobs'];

        if ($params['failed-jobs']) {
            $headers[] = 'Failed Jobs';
        }

        while(true) {
            if ($params['failed-jobs']) {
                $countFailedJobs = $this->getCountFailedJobs($params['queues']);
            }

            $rows = [];
            foreach ($params['queues'] as $queue) {
                $row = [
                    '<info>' . $queue . '</info>',
                    \Queue::size($queue),
                ];

                if ($params['failed-jobs']) {
                    $row[] = $countFailedJobs[$queue] ?? 0;
                }

                $rows[] = $row;
            }

            $this->table($headers, $rows);

            if ($params['delay'] > 0) {
                sleep($params['delay']);
            } else {
                break;
            }

            $this->removeLines(count($rows) + 4);
        }
    }

    /**
     * Get number of failed jobs
     *
     * @param array $queues
     * @return array
     */
    protected function getCountFailedJobs(array $queues)
    {
        try {
            return \DB::table('failed_jobs')
                ->select(\DB::raw('count(*) as count'), 'queue')
                ->where('connection', '=', \Queue::getConnectionName())
                ->whereIn('queue', $queues)
                ->groupBy('queue')
                ->get()
                ->keyBy('queue')
                ->map(function ($row) {
                    return $row->count;
                })
                ->all();
        } catch (\Illuminate\Database\QueryException $exception) {
            return [];
        }
    }

    /**
     * Get all parameters
     *
     * @return array
     */
    protected function getParams()
    {
        $presetName = $this->option('preset');
        if (! empty($presetName)) {
            $config = config('queuesize');

            $preset = $config[$presetName] ?? [];

            return [
                'delay' => $preset['delay'] ?? 0,
                'queues' => $preset['queues'] ?? [],
                'failed-jobs' => $preset['failed-jobs'] ?? false,
            ];
        } else {
            return [
                'delay' => $this->option('delay'),
                'queues' => array_filter($this->option('queue')),
                'failed-jobs' => $this->option('failed-jobs'),
            ];
        }
    }

    /**
     * Remove specified number of lines
     *
     * @param int $countLines
     */
    protected function removeLines(int $countLines)
    {
        for ($lineIndex = 0; $lineIndex < $countLines; $lineIndex++) {
            $this->output->write("\033[1A");
        }
    }
}
