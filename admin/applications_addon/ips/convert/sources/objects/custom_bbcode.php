
/**
 * Convert a custom bbcode
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	string 		How to handle duplicates ('local' or 'remote')
 * @return 	boolean		Success or fail
 **/
public function convertBBCode($id, $info, $dupes)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['bbcode_title'])
	{
		$this->logError($id, 'No title provided');
		return false;
	}
	if (!$info['bbcode_tag'])
	{
		$this->logError($id, 'No tag provided');
		return false;
	}
	if (!$info['bbcode_replace'] and !$info['bbcode_php_plugin'])
	{
		$this->logError($id, 'No replacement provided');
		return false;
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	$codes = $this->DB->build( array( 'select' => 'bbcode_id, bbcode_tag, bbcode_aliases', 'from' => 'custom_bbcode' ) );
	$this->DB->execute();

	while ($row = $this->DB->fetch())
	{

		$aliases = array();
		$aliases = explode(',', $row['bbcode_aliases']);

		if ($row['bbcode_tag'] == $info['bbcode_tag'] || in_array($info['bbcode_tag'], $aliases)) {

			if ($dupes == 'local')
			{
				return false;
			}
			else
			{
				$this->DB->delete('custom_bbcode', "bbcode_id={$row['bbcode_id']}");
			}

		}

	}

	// Strip spaces from tag
	$info['bbcode_tag'] = str_replace(' ', '', $info['bbcode_tag']);

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['bbcode_image'] = isset( $info['bbcode_image'] ) ? $info['bbcode_image'] : '';

	unset($info['bbcode_id']);

	// dropped columns
	unset($info['bbcode_parse']);

	$this->DB->insert( 'custom_bbcode', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'custom_bbcode');

	return true;
}
