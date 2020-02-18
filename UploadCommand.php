<?PHP
namespace GuzzleTest\Command;

use GuzzleTest\Traits\GetToken;
use GuzzleTest\Traits\GetFileList;
use GuzzleTest\Model\SynchronousUploader;
use GuzzleTest\Model\AsynchronousUploader;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class UploadCommand extends Command
{
    use GetToken;
    use GetFileList;

    protected $debug = false;
    protected $maxFiles = null;

    /**
     * Called by the application, this method sets up the command.
     */
    protected function configure()
    {
        $definition = [
          new InputOption('async', 'a', InputOption::VALUE_NONE, 'Process upload asynchronously.'),
          new InputOption('sync', 's', InputOption::VALUE_NONE, 'Process upload synchronously.'),
          new InputOption('count', 'c', InputOption::VALUE_REQUIRED, 'The number of files to process.'),
        ];

        $option = new InputOption('foo', null, InputOption::VALUE_NONE);

        $this->setName('upload')
            ->setDescription('run upload test')
            ->setDefinition($definition)
            ->setHelp("Upload all the test files and display times.");
        return;
    }

    /**
     * Main body of this command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Begin Synchronous uploads', OutputInterface::VERBOSITY_NORMAL);

        if ($input->getOption('sync') && $input->getOption('async')) {
            throw new \Exception('Cannot process both synchronously and asynchronously');
        }
        $uploader = null;

        if ($input->getOption('sync')) {
            $uploader = new SynchronousUploader();
        } else if ($input->getOption('async')) {
            $uploader = new AsynchronousUploader();
        }

        if (is_null($uploader)) {
            throw new \Exception('You must choose either --sync or --async');
        }

        $this->maxFiles = (int) $input->getOption('count');

        $this->debug = $output->isDebug();
        $this->getToken($this->debug);
        $fileList = $this->getFileList();

        if (is_null($this->maxFiles) ||
            $this->maxFiles > count($fileList) ||
            $this->maxFiles < 1) {
            $this->maxFiles = count($fileList);
        }
        $begintime = microtime(true);
        $output->writeln('Processing ' . $this->maxFiles . ' files.', OutputInterface::VERBOSITY_VERBOSE);

        $fileCount = $uploader->execute(
            $fileList,
            $this->getApplication()->config['baseurl'],
            $this->getApplication()->config['paths']['data'],
            $this->debug,
            $this->token['token'],
            $this->maxFiles
        );
        $endtime = microtime(true);

        $elapsedSeconds = $endtime - $begintime;

        $output->writeln('Files Uploaded   : ' . $fileCount, OutputInterface::VERBOSITY_NORMAL);
        $output->writeln('Seconds          : ' . $elapsedSeconds, OutputInterface::VERBOSITY_NORMAL);
        $output->writeln('Seconds per File : ' . $elapsedSeconds / $fileCount, OutputInterface::VERBOSITY_NORMAL);

        $output->writeln('Done', OutputInterface::VERBOSITY_NORMAL);

        return 1;
    }
}
