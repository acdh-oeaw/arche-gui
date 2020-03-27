CREATE OR REPLACE FUNCTION gui.detail_view_func(_identifier text)
    RETURNS table (id bigint, property text, type text, value text, relvalue text, acdhid text, accessRestriction text )
    
AS $func$
DECLARE
	_main_id bigint := (select i.id from identifiers as i where i.ids =_identifier);
BEGIN
	DROP TABLE IF EXISTS detail_meta;
	CREATE TEMPORARY TABLE detail_meta AS (
		select mv.id, mv.property, mv.type, mv.value
		from metadata_view as mv 
		where mv.id = _main_id				
		union
		select m.id, m.property, m.type, m.value
		from metadata as m 
		where m.id = _main_id
	);

	DROP TABLE IF EXISTS detail_meta_rel;
	CREATE TEMPORARY TABLE detail_meta_rel AS (
	select DISTINCT(CAST(m.id as VARCHAR)), m.value,  i.ids as acdhId
	from metadata as m
	left join detail_meta as dm on CAST(dm.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
	left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%/id.acdh.oeaw.ac.at/uuid/%' as varchar)
	where dm.type = 'REL' );
	
	RETURN QUERY
	select dm.id, dm.property, dm.type, dm.value, dmr.value as relvalue, dmr.acdhid,
	CASE WHEN dm.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' THEN dmr.value
	ELSE ''
	END
	from detail_meta as dm
	left join detail_meta_rel as dmr on dmr.id = dm.value
	order by property; 
END
$func$
LANGUAGE 'plpgsql';

select * from gui.detail_view_func('https://repo.hephaistos.arz.oeaw.ac.at/51347')
