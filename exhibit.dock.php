<?php if (!defined('SITE')) exit('No direct script access allowed');

/**
* Dock
* Exhbition format
*
* @version 1.0
* @author Taylor Boyko, taylor@wrprojects.com 
*
*/

// Styling Options
$dock = new Dock({
	'headerHeight' => 110,
	'scaleImagesBeyondDimensions' => true
});

$exhibit['exhibit'] = $dock->createExhibit();
$exhibit['dyn_css'] = $dock->dynamicCSS();
$exhibit['dyn_js'] = $dock->dynamicJS();

class Dock
{
	var $headerHeight;
	var $imgSizePre;
	
	function __construct($settings)
	{
		$this->headerHeight = $settings['headerHeight'];
		$this->imgSizePre = ($settings['scaleImagesBeyondDimensions']) ? "" : "max-";
	}
	
	function createExhibit()
	{
		$OBJ =& get_instance();
		global $rs;

		$pictures = $OBJ->db->fetchArray("SELECT * 
			FROM ".PX."media, ".PX."objects_prefs 
			WHERE media_ref_id = '$rs[id]' 
			AND obj_ref_type = 'exhibit' 
			AND obj_ref_type = media_obj_type 
			ORDER BY media_order ASC, media_id ASC");
		
		$thumbnailHtml = '';
		for ($i=0; $i<count($pictures); $i++)
		{
			$thumbnailHtml .= "<img />";
		}	

		return "
		<div id=\"image-container\"><img /></div>
		
		<div id=\"thumbnail-bar\">" . $thumbnailHtml . "</div>
		
		<div id=\"thumbnail-hotspot\"></div>
		";
	}

	function dynamicJS()
	{
		$OBJ =& get_instance();
		global $rs;

		$pictures = $OBJ->db->fetchArray("SELECT * 
			FROM ".PX."media, ".PX."objects_prefs 
			WHERE media_ref_id = '$rs[id]' 
			AND obj_ref_type = 'exhibit' 
			AND obj_ref_type = media_obj_type 
			ORDER BY media_order ASC, media_id ASC");
		
		$jsonImageArray = '{ ';
		for($i=0;$i<count($pictures);$i++)
		{
			$picture = $pictures[$i];
			$jsonImageArray .= $i . ':' . "'" . BASEURL . GIMGS . "/" . $picture[media_file] . "',";
		}
		$jsonImageArray = substr($jsonImageArray,0,strlen($jsonImageArray)-1) . '}';
		
		return "
			var TD = {
				jq: {
					image: null,
					imageContainer: null,
					thumbnailBar: null,
					thumbnailHotspot: null,
					thumbnails: null
				},
				timer: null,
				Initialize: function() {
					TD.jq.imageContainer = $('#image-container');
					TD.jq.image = $('img', TD.jq.imageContainer);
					TD.jq.thumbnailBar = $('#thumbnail-bar');
					TD.jq.thumbnails = $('img', TD.jq.thumbnailBar);
					TD.jq.thumbnailHotspot = $('#thumbnail-hotspot');
					
					TD.jq.image.attr('src', TD.imageArray[0]).load(function() {
						TD.ResizeImage(); 
						TD.jq.image.animate({opacity: 1}, 300);
					});
					
					TD.jq.thumbnails.each( function(index) {
						$(this).attr('src', TD.imageArray[index]).load(TD.ShowThumbnailDockOnLoad);
					});
					
					TD.jq.image.data('imageNumber', 0);
					TD.jq.thumbnails.each(function (index) {
						$(this).data('imageNumber', index);
					});
					
					$(window).resize(TD.ResizeImage);
					
					TD.jq.thumbnailHotspot.mouseenter(TD.ShowThumbnailDock);
					
					TD.jq.thumbnailBar.hover(
						function() {
							clearTimeout(TD.timer);
						},
						TD.TimerHideThumbnailDock
					);
					
					TD.jq.thumbnails
						.css('opacity', .5)
						.hover(
							function () {
								var _this = $(this);
								if (_this.data('imageNumber') != TD.selectedImageNumber) $(this).animate({opacity: .75}, 150);
							},
							function () {
								var _this = $(this);
								if (_this.data('imageNumber') != TD.selectedImageNumber) _this.animate({opacity: .5}, 150);
							}
						)
						.click( function() {
							TD.ChangeImage($(this).attr('src'), $(this).data('imageNumber'))
						});
					TD.jq.thumbnails.first().css('opacity', 1);
					
					TD.jq.image.click(function() {
						var nextImageNumber = parseInt($(this).data('imageNumber')) + 1;
						if (nextImageNumber >= TD.imageArraySize) nextImageNumber = 0;
						TD.ChangeImage(TD.imageArray[nextImageNumber], nextImageNumber);
					});
				},
				ChangeImage: function(imageSrc, imageNumber) {
					TD.jq.image
						.css('opacity', 0)
						.attr('src', imageSrc)
						.data('imageNumber', imageNumber)
						.animate({opacity: 1}, 300);
					TD.ResizeImage();
					TD.selectedImageNumber = imageNumber;
					TD.jq.thumbnails.each( function() {
						var _this = $(this);
						if ( _this.data('imageNumber') == imageNumber ) {
							_this.animate({opacity: 1}, 150);
						} else {
							_this.animate({opacity: .5}, 150);
						}
					});
				},
				ShowThumbnailDockOnLoad: function() {
					if (++TD.imagesLoaded == TD.imageArraySize) TD.ShowThumbnailDock();
				},
				ShowThumbnailDock: function() {
					TD.jq.thumbnailHotspot.hide();
					TD.jq.thumbnailBar.animate({bottom: 0}, 350);
				},
				TimerHideThumbnailDock: function() {
					TD.timer = setTimeout('TD.HideThumbnailDock()', 1500);
				},
				HideThumbnailDock: function() {
					TD.jq.thumbnailBar.animate({bottom: -60}, 350);
					TD.jq.thumbnailHotspot.show();
				},
				ResizeImage: function() {
					TD.jq.image
						.css('visibility', 'hidden')
						.css('" . $this->imgSizePre . "height', '100%')
						.css('width', 'auto');
					TD.jq.imageContainer
						.css('height', window.innerHeight - 90 - " . $this->headerHeight . ");
					if (TD.jq.image.width() >= TD.jq.imageContainer.width()) {
						TD.jq.image
							.css('height', 'auto')
							.css('" . $this->imgSizePre . "width', '100%');
					}
					TD.jq.image.css('visibility', 'visible');
				},
				imageArray: " . $jsonImageArray . ",
				imageArraySize: " . count($pictures) . ",
				imagesLoaded: 0,
				selectedImageNumber: 0
			};
			
			$(function() {
				TD.Initialize();
			});
			";
	}

	function dynamicCSS()
	{
		return "
		#image-container {
			margin: 0;
			padding: 0;
			height: 100%;
			width: 100%;
			text-align: center;
		}
		
		#thumbnail-hotspot, #thumbnail-bar {
			text-align: center;
			width: 990px;
			height: 55px;
			position: fixed;
			bottom: 0px;
		}
		
		#thumbnail-bar {
			bottom: -60px;
		}
				
		#thumbnail-bar > img {
			padding-right: 1px;
			height: 55px;
			width: auto;
		}
		
		#image-container > img {
			" . $this->imgSizePre . "height: 100%;
			width: auto;
			opacity: 0;
		}
		";
	}
}
