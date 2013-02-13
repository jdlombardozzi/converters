
/**
 * Convert a form field
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertFormField($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['name'])
	{
		$this->logError($id, 'No name provided');
		return false;
	}
	if (!$info['type'])
	{
		$this->logError($id, 'No type provided');
		return false;
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	$dupe = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'gallery_form_fields', 'where' => "name = '{$info['name']}'" ) );
	if ($dupe)
	{
		$this->addLink($dupe['id'], $id, 'gallery_form_fields');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['id']);
	$this->DB->insert( 'gallery_form_fields', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add a column
	//-----------------------------------------

	$this->DB->addField( 'gallery_images', "field_$inserted_id", 'text' );
	$this->DB->optimize( 'gallery_images' );

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'gallery_form_fields');

	return true;
}
