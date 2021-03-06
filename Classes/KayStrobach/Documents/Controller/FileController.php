<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 13.12.14
 * Time: 09:22
 */

namespace KayStrobach\Documents\Controller;

use KayStrobach\Documents\Domain\Model\File;
use KayStrobach\Documents\Domain\Model\Folder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Exception\StopActionException;

class FileController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \KayStrobach\Documents\Domain\Repository\FileRepository
	 */
	protected $fileRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @param Folder $parentFolder
	 */
	public function newAction(Folder $parentFolder) {
		$this->view->assign('parentFolder', $parentFolder);
	}

	/**
	 * Upload a file
	 *
	 * @param File $file
	 */
	public function createAction(File $file) {
		$file->setName($file->getOriginalResource()->getFilename());
		$this->fileRepository->add($file);
		$this->redirect(
			'index',
			'folder',
			NULL,
			array(
				'folder' => $file->getParentFolder()
			)
		);
	}

	/**
	 * edit a file
	 *
	 * @param File $file
	 */
	public function editAction(File $file) {
		$this->view->assign('file', $file);
	}

	/**
	 * update a file
	 *
	 * @param File $file
	 */
	public function updateAction(File $file) {
		$this->fileRepository->update($file);
		$this->redirect(
			'index',
			'folder',
			NULL,
			array(
				'folder' => $file->getParentFolder()
			)
		);
	}

	/**
	 * @param File $file
	 * @return string
	 */
	public function downloadAction(File $file) {
		$this->response->setHeader('Content-Type', $file->getOriginalResource()->getMediaType());
		$this->response->setHeader('Content-Length', @filesize('resource://' . $file->getOriginalResource()->getResourcePointer()->getHash()));
		$this->response->setHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
		$buffer = @file_get_contents('resource://' . $file->getOriginalResource()->getResourcePointer());
		return $buffer;
	}

	/**
	 * removes a file
	 *
	 * @param File $file
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeAction(File $file) {
		$this->fileRepository->remove($file);
		$this->redirect('index', 'folder', NULL, array('folder' => $file->getParentFolder()));
	}

	/**
	 * action for html5 multifile upload
	 *
	 * @param Folder $folder
	 */
	public function multiUploadAction(Folder $folder) {
		if (isset($_FILES) && !empty($_FILES)) {
			$count = 0;
			foreach ($_FILES as $file) {
				foreach ($file['name'] as $filename) {
					if ($file['name'][$count] != "") {

						$resource = array(
							'tmp_name' => $file['tmp_name'][$count],
							'name'     => $file['name'][$count]
						);
						/** @var \TYPO3\Flow\Resource\Resource $newResource */
						$newResource = $this->resourceManager->importUploadedResource($resource);

						$newFile = new File();
						$newFile->setParentFolder($folder);
						$newFile->setOriginalResource($newResource);
						$newFile->setName($newResource->getFilename());
						$this->fileRepository->add($newFile);
						$count++;
					}
				}
			}
		}
		$this->redirect(
			'index',
			'Folder',
			NULL,
			array(
				'folder' => $folder
			)
		);
	}

}