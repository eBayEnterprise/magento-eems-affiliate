<?php
/**
 * Create a temp directory - location the tar archive will be created at
 * and return the path to the created temp dir
 * @return string
 */
function _createTempDir() {
    // this will create a temp file but as we need a temp
    // directory, delete the file and replace it with a directory
    $dir = tempnam(sys_get_temp_dir(), '_archtmp');
    @unlink($dir);
    @mkdir($dir, 0700);
    return $dir;
}
/**
 * Create a tar or the extension and return the location of the create .tar
 * @return string
 */
function _createArchive($location, $archiveName, array $extensions=array()) {
    $path = $location . DIRECTORY_SEPARATOR . $archiveName;
    echo "Creating archive @ $path\n";
    $cmdPat = "cd '%s' && tar --exclude 'EbayEnterprise/*/Test' --exclude='order_id_init.php' -rpf '$path' .";
    shell_exec(sprintf($cmdPat, 'src'));
    foreach($extensions as $dir) {
        shell_exec(sprintf($cmdPat, "extensions/$dir/src"));
    }
    return $path;
}
// Get the latest reachable tag as the current, to become old version
$newVersion = trim(shell_exec('git describe --abbrev=0'));
$oldVersion = trim(shell_exec(sprintf('git describe --abbrev=0 %s~', $newVersion)));
echo "Setup config for upgrade from {$oldVersion} to {$newVersion}\n";
$tempDir = _createTempDir();
$archiveName = 'eBay_Enterprise_Affiliate_Extension.tar';
_createArchive($tempDir, $archiveName);
$releaseNotes = trim(shell_exec(sprintf("git log --pretty=format:'%%s' \"%s\"..", $oldVersion)));

return array(

    //The base_dir and archive_file path are combined to point to your tar archive
    //The basic idea is a seperate process builds the tar file, then this finds it
    'base_dir'               => $tempDir,
    'archive_files'          => $archiveName,

    //The Magento Connect extension name.  Must be unique on Magento Connect
    //Has no relation to your code module name.  Will be the Connect extension name
    'extension_name'         => 'eBay_Enterprise_Affiliate_Extension',

    //Your extension version.  By default, if you're creating an extension from a
    //single Magento module, the tar-to-connect script will look to make sure this
    //matches the module version.  You can skip this check by setting the
    //skip_version_compare value to true
    'extension_version'      => $newVersion,
    'skip_version_compare'   => true,

    //Where on your local system you'd like to build the files to
    'path_output'            => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'build',

    //Magento Connect stability.
    'stability'              => 'stable',

    //Magento Connect license value
    'license'                => 'EULA',

    //Magento Connect channel value.  This should almost always (always?) be community
    'channel'                => 'community',

    //Magento Connect information fields.
    'summary'                => 'eBay Enterprise Affiliate Extension.',
    'description'            => 'eBay Enterprise Affiliate Extension.',
    'notes'                  => $releaseNotes,

    //Magento Connect author information. If author_email is foo@example.com, script will
    //prompt you for the correct name.  Should match your http://www.magentocommerce.com/
    //login email address
    'author_name'    => 'Michael A. Smith',
    'author_user'    => 'msmith3',
    'author_email'   => 'msmith3@ebay.com',

    'additional_authors' => array(
        array(
            'author_name'    => 'Michael Phang',
            'author_user'    => 'mphang',
            'author_email'   => 'mphang@ebay.com',
        ),
        array(
            'author_name'    => 'Mike West',
            'author_user'    => 'micwest',
            'author_email'   => 'micwest@ebay.com',
        ),
        array(
            'author_name'    => 'Reginald Gabriel',
            'author_user'    => 'rgabriel',
            'author_email'   => 'rgabriel@ebay.com',
        ),
        array(
            'author_name'    => 'Scott van Brug',
            'author_user'    => 'svanbrug',
            'author_email'   => 'svanbrug@ebay.com',
        ),
    ),


    //PHP min/max fields for Connect.  I don't know if anyone uses these, but you should
    //probably check that they're accurate
    'php_min'                => '5.3.0',
    'php_max'                => '5.6.99',

    //PHP extension dependencies. An array containing one or more of either:
    //  - a single string (the name of the extension dependency); use this if the
    //    extension version does not matter
    //  - an associative array with 'name', 'min', and 'max' keys which correspond
    //    to the extension's name and min/max required versions
    //Example:
    //    array('json', array('name' => 'mongo', 'min' => '1.3.0', 'max' => '1.4.0'))
    'extensions'             => array()
);
