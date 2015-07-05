<?php namespace October\Rain\Cron\Console;

use Illuminate\Console\Command;
use October\Rain\Cron\Models\Job;
use October\Rain\Cron\CronJob;
use Symfony\Component\Console\Input\InputOption;

class CronCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Executes the job in the cron queue";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $t = \Carbon\Carbon::now();
        $jobs= Job::isAvailable()
                ->selectRaw('*, (created_at + INTERVAL (delay-59) SECOND ) as timetogo')
                ->where('retries','<',$this->option('tries'))
                ->havingRaw('timetogo <= ?', [$t])
                ->orderByRaw('timetogo ASC')
                ->take( $this->option('package') )
                ->get();
        if( $jobs->count() > 0 ) {
            
            $started = $jobs->lists('id');
            Job::whereIn('id', $started)->update(['status' => Job::STATUS_WAITING]);

            foreach($jobs as $job){
                $cronJob = new CronJob($this->laravel, $job);
                $cronJob->fire();
            }
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('tries', null, InputOption::VALUE_OPTIONAL, 'Number of tries to execute the job.', 3),
            array('package', null, InputOption::VALUE_OPTIONAL, 'Number of job to execute at once.', 5),
        );
    }

}
