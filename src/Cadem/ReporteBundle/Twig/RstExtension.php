<?php

namespace Cadem\ReporteBundle\Twig;

use Symfony\Component\Process\Process;

class RstExtension extends \Twig_Extension
{
	public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('rst2html', array($this, 'rst2htmlFilter')),
        );
    }
	
	public function rst2htmlFilter($rst)
    {
		if(file_exists('C:\docutils\tools\rst2html.py')){
			// --initial-header-level=3 to begin titles at the h3 tag
			$process = new Process('C:\docutils\tools\rst2html.py --no-doc-title --initial-header-level=3');
			$process->setStdin($rst);
			$process->run();
			if (!$process->isSuccessful()) {
				throw new \RuntimeException($process->getErrorOutput());
			}
			$html = $process->getOutput();

			$startpos = strpos($html, '<body>') + 6 + 24;
			$endpos   = strpos($html, '</body>') - 8;
			
			return substr($html, $startpos, $endpos - $startpos);
		}
		else return $rst;
    }

    public function getName()
    {
        return 'rst_extension';
    }


}
