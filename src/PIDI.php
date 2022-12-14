<?php
	/*
		   ___  _______  ____
		  / _ \/  _/ _ \/  _/
		 / ___// // // // /
		/_/  /___/____/___/

		Manipulate PDF's
	*/

	namespace PIDI;
	use mikehaertl\shellcommand\Command;

	class PIDI {

		private $info;
		private $file;

		function __construct($file) {

			$command = new Command("qpdf {$file} --json");
			$this->file = $file;

			// No file present
			if(!file_exists($file)) {

				throw new \Exception("PIDI Error: File {$file} not found");
				return [];
			}

			// Not executable
			if (!$command->execute()) {
				throw new \Exception('PIDI Error: ' . $command->getError());
				return [];
			}

			$this->info = json_decode($command->getOutput());

			$this->pages = $this->getPages();
		}

		static function getFieldType($field) {

			if($field->ischeckbox) return 'checkbox';
			if($field->ischoice) return 'choice';
			if($field->isradiobutton) return 'radio';
			if($field->istext) return 'text';
		}

		public function extractObjectPage($name) {

			$obj_name = "obj:$name";
			return $this->info->qpdf[1]->{$obj_name}->value->{'/P'};
		}

		public function extractObjectCoordinate($name, $path = '/Rect') {

			$obj_name = "obj:$name";
			return $this->info->qpdf[1]->{$obj_name}->value->{$path};
		}

		public function extractPageSize($name) {

			return [$this->pages[$name]->info[2], $this->pages[$name]->info[3]];
		}

		public function extractFormFieldInfo($name, $path = '/Rect') {

			$obj_name = "obj:$name";

			list($page_width, $page_height) = $this->extractPageSize($this->extractObjectPage($name));

			$rect = $this->extractObjectCoordinate($name, $path);

			$width = $rect[2] - $rect[0];
			$height = $rect[3] - $rect[1];
			$x = $rect[0];
			$y = $page_height - $rect[1] - $height;

			return [
				'absolute' => [
					'x' => $x,
					'y' => $y,
					'width' => $width,
					'height' => $height,
				],
				'relative' => [
					'x' => $rect[0]*100/$page_width,
					'y' => $y*100/$page_height,
					'width' => ($rect[2] - $rect[0])*100/$page_width,
					'height' => ($rect[3] - $rect[1])*100/$page_height,
				]
			];
		}

		public function generatePageImages($name = '') {

			$path_parts = pathinfo($this->file);

			$dir = dirname($this->file);
			$file_name = $name ?: $path_parts['filename'];

			$gs_exe = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'gswin64.exe' : 'gs';
			$command = new Command($gs_exe . ' -dNOPAUSE -dBATCH -sDEVICE=png16m -r600 -sOutputFile="' . $dir . '/' . $file_name . '-%d.png" ' . $this->file);
			$command->execute();
		}

		public function getPages() {

			$pages = [];

			foreach($this->info->pages as $page) {

				$the_page = new \stdClass();
				$the_page->object = $page->object;
				$the_page->info = $this->extractObjectCoordinate($page->object, '/ArtBox');

				$pages[$page->object] = $the_page;
			}

			return $pages;
		}

		public static function slugify($text, string $divider = '-') {
			// replace non letter or digits by divider
			$text = preg_replace('~[^\pL\d]+~u', $divider, $text);

			// transliterate
			$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

			// remove unwanted characters
			$text = preg_replace('~[^-\w]+~', '', $text);

			// trim
			$text = trim($text, $divider);

			// remove duplicate divider
			$text = preg_replace('~-+~', $divider, $text);

			// lowercase
			$text = strtolower($text);

			if (empty($text)) {
				return 'n-a';
			}

			return $text;
		}

		public function getFormFields() {

			// Check for acroform fields

			$fields = [];

			if(isset($this->info->acroform) && isset($this->info->acroform->fields)) {

				foreach($this->info->acroform->fields as $field) {

					$the_field = new \stdClass();

					$the_field->name = $field->alternativename;
					$the_field->object = self::slugify($field->object);
					$the_field->page = $this->extractObjectPage($field->object);
					$the_field->type = self::getFieldType($field);
					$the_field->info = self::extractFormFieldInfo($the_field->object);

					$fields[] = $the_field;

				}
			}

			return $fields;
		}
	}