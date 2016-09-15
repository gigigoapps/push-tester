<?php

namespace PushTester\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * This command is inspired by the self-update command included in symfony installer
 */
class SelfUpdateCommand extends Command
{
    private $tempDir;

    /** @var OutputInterface */
    private $output;

    /** @var Filesystem */
    private $fs;

    /** @var string */
    private $latestInstallerVersion;

    /** @var string  the URL where the latest installer version can be downloaded */
    private $remoteInstallerFile;

    /** @var string the filepath of the installer currently installed in the local machine */
    private $currentInstallerFile;

    /** @var string the filepath of the new installer downloaded to replace the current installer */
    private $newInstallerFile;

    /** @var string the filepath of the backup of the current installer in case a rollback is performed */
    private $currentInstallerBackupFile;

    /** @var bool flag which indicates that, in case of a rollback, it's safe to restore the installer backup because it corresponds to the most recent version */
    private $restorePreviousInstaller;

    const INSTALLER_URL = 'https://github.com/gigigoapps/push-tester/releases/download/latest/PushTester.phar';
    const VERSIONS_URL = 'https://github.com/gigigoapps/push-tester/releases/download/latest/push-tester.version';

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(array('selfupdate'))
            ->setDescription('Update this application to the latest version.')
            ->setHelp('The <info>%command.name%</info> command updates the installer to the latest available version.')
        ;
    }

    /**
     * The self-update command is only available when using the installer via the PHAR file.
     */
    public function isEnabled()
    {
        return 'phar://' === substr(__DIR__, 0, 7);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $this->output = $output;

        $this->latestInstallerVersion = $this->getUrlContents(self::VERSIONS_URL);
        $this->remoteInstallerFile = self::INSTALLER_URL;
        $this->currentInstallerFile = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        $this->tempDir = sys_get_temp_dir();
        $this->currentInstallerBackupFile = basename($this->currentInstallerFile, '.phar').'-backup.phar';
        $this->newInstallerFile = $this->tempDir.'/'.basename($this->currentInstallerFile, '.phar').'-temp.phar';
        $this->restorePreviousInstaller = false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->isInstallerUpdated()) {
            $this->output->writeln(sprintf('// PushTester is <info>already updated</info> to the latest version (%s).', $this->latestInstallerVersion));

            return;
        } else {
            $this->output->writeln(sprintf('// <info>updating</info> PushTester to <info>%s</info> version', $this->latestInstallerVersion));
        }

        try {
            $this
                ->downloadNewVersion()
                ->checkNewVersionIsValid()
                ->backupCurrentVersion()
                ->replaceCurrentVersionbyNewVersion()
                ->cleanUp()
            ;
        } catch (IOException $e) {
            if ($this->output->isVeryVerbose()) {
                $this->output->writeln($e->getMessage());
            }

            throw new \RuntimeException(sprintf(
                "The installer couldn't be updated, probably because of a permissions issue.\n".
                "Try to execute the command again with super user privileges:\n".
                "  sudo %s\n",
                implode(' ', $_SERVER['argv'])
            ));
        } catch (\Exception $e) {
            $this->rollback();

            if ($this->output->isVeryVerbose()) {
                $this->output->writeln($e->getMessage());
            }
        }
    }

    private function downloadNewVersion()
    {
        // check for permissions in local filesystem before start downloading files
        if (!is_writable($this->currentInstallerFile)) {
            throw new \RuntimeException('PushTester update failed: the "'.$this->currentInstallerFile.'" file could not be written');
        }

        if (!is_writable($this->tempDir)) {
            throw new \RuntimeException('PushTester update failed: the "'.$this->tempDir.'" directory used to download files temporarily could not be written');
        }

        if (false === $newInstaller = $this->getUrlContents($this->remoteInstallerFile)) {
            throw new \RuntimeException('The new version of the PushTester couldn\'t be downloaded from the server.');
        }

        $newInstallerPermissions = $this->currentInstallerFile ? fileperms($this->currentInstallerFile) : 0777 & ~umask();
        $this->fs->dumpFile($this->newInstallerFile, $newInstaller, $newInstallerPermissions);

        return $this;
    }

    private function checkNewVersionIsValid()
    {
        // creating a Phar instance for an existing file is not allowed
        // when the Phar extension is in readonly mode
        if (!ini_get('phar.readonly')) {
            // test the phar validity
            $phar = new \Phar($this->newInstallerFile);

            // free the variable to unlock the file
            unset($phar);
        }

        return $this;
    }

    private function backupCurrentVersion()
    {
        $this->fs->copy($this->currentInstallerFile, $this->currentInstallerBackupFile, true);
        $this->restorePreviousInstaller = true;

        return $this;
    }

    private function replaceCurrentVersionbyNewVersion()
    {
        $this->fs->copy($this->newInstallerFile, $this->currentInstallerFile, true);

        return $this;
    }

    private function cleanUp()
    {
        $this->fs->remove(array($this->currentInstallerBackupFile, $this->newInstallerFile));
    }

    private function rollback()
    {
        $this->output->writeln(array(
            '',
            'There was an error while updating the installer.',
            'The previous PushTester version has been restored.',
            '',
        ));

        $this->fs->remove($this->newInstallerFile);

        if ($this->restorePreviousInstaller) {
            $this->fs->copy($this->currentInstallerBackupFile, $this->currentInstallerFile, true);
        }
    }

    /**
     * @return boolean Whether the installed version is the latest one
     */
    protected function isInstallerUpdated()
    {
        $installedVersion = $this->getApplication()->getVersion();
        $latestVersion = $this->getUrlContents(self::VERSIONS_URL);

        return version_compare($installedVersion, $latestVersion, '>=');
    }

    /**
     * Returns the contents obtained by making a GET request to the given URL.
     *
     * @param string $url
     *
     * @return string
     */
    protected function getUrlContents($url)
    {
        return file_get_contents($url);
    }
}
