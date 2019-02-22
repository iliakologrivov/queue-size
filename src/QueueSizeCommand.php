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
                            {--queue=* : Queue name}
                            {--delay=0 : Delay in seconds}
                            {--failed-jobs : Show count failed jobs}
                            {--p|preset= : Preset name}
                            {--f|full-info : Show more information on queue size (only redis)}
                            {--min : Minimal display view}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View queue size';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $params = $this->getParams();

        if (empty($params['queues'])) {
            return $this->error('Queues not specified!');
        }

        if ($params['full-info']) {
            $headers = ['Queue name', 'Total count jobs', 'On queue', 'On delayed', 'On reserve'];
        } else {
            $headers = ['Queue name', 'Count jobs'];
        }

        if ($params['failed-jobs']) {
            $headers[] = 'Failed Jobs';
        }

        while(true) {
            if ($params['failed-jobs']) {
                $countFailedJobs = $this->getCountFailedJobs($params['queues']);
            }

            $rows = [];
            foreach ($params['queues'] as $queue) {
                if ($params['full-info']) {
                    list($onQuue, $onDelayed, $onReserve) = $this->getFullSizeInfo($queue);

                    $row = [
                        $queue,
                        $onQuue + $onDelayed + $onReserve,
                        $onQuue,
                        $onDelayed,
                        $onReserve
                    ];
                } else {
                    $row = [
                        '<info>' . $queue . '</info>',
                        \Queue::size($queue),
                    ];
                }

                if ($params['failed-jobs']) {
                    $row[] = $countFailedJobs[$queue] ?? 0;
                }

                $rows[] = $row;
            }

            if ($params['min']) {
                $this->output->write(implode(PHP_EOL, array_map(function($row){
                    return implode(' ', $row);
                }, $rows)) . PHP_EOL);

                $countRemoveLines = count($rows);
            } else {
                $this->table($headers, $rows);

                $countRemoveLines = count($rows) + 4;
            }

            if ($params['delay'] > 0) {
                sleep($params['delay']);
            } else {
                break;
            }

            $this->removeLines($countRemoveLines);
        }
    }

    /**
     * Get number of failed jobs
     *
     * @param array $queues
     * @return array
     */
    protected function getCountFailedJobs(array $queues): array
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
    protected function getParams(): array
    {
        $presetName = $this->option('preset');
        if (! empty($presetName)) {
            $config = config('queuesize');

            $preset = $config[$presetName] ?? [];

            return [
                'delay' => $preset['delay'] ?? $this->option('delay'),
                'queues' => $preset['queues'] ?? array_filter($this->option('queue')),
                'failed-jobs' => $preset['failed-jobs'] ?? $this->option('failed-jobs'),
                'full-info' => $preset['full-info'] ?? $this->option('full-info'),
                'min' => $preset['min'] ?? $this->option('min'),
            ];
        } else {
            return [
                'delay' => $this->option('delay'),
                'queues' => array_filter($this->option('queue')),
                'failed-jobs' => $this->option('failed-jobs'),
                'full-info' => $this->option('full-info'),
                'min' => $this->option('min'),
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
        $this->output->write("\x0D" . str_repeat("\033[1A", $countLines));
    }

    /**
     * Get more info of queue size.
     *
     * @param string $queue
     * @return mixed
     */
    protected function getFullSizeInfo(string $queue): array
    {
        return \Illuminate\Support\Facades\Redis::eval(
            "return {redis.call('llen', KEYS[1]), redis.call('zcard', KEYS[2]), redis.call('zcard', KEYS[3])}", 3, 'queues:' . $queue, 'queues:' . $queue . ':delayed', 'queues:' . $queue . ':reserved'
        );
    }
}
