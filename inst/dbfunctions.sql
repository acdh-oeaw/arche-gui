/*
* DETAIL VIEW METADATA FUNCTION 
*/
CREATE OR REPLACE FUNCTION gui.detail_view_func(_identifier text)
    RETURNS table (id bigint, property text, type text, value text, relvalue text, acdhid text, accessRestriction text )
    
AS $func$
BEGIN
	DROP TABLE IF EXISTS detail_meta;
	CREATE TEMPORARY TABLE detail_meta AS (
	select mv.id, mv.property, mv.type, mv.value
	from identifiers as i
	inner join metadata_view as mv on mv.id = i.id
	where i.ids = _identifier
	union
	select m.id, m.property, m.type, m.value
	from identifiers as i
	inner join metadata as m on m.id = i.id
	where i.ids = _identifier
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
	(select r.val from raw as r where r.prop = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and r.id = dm.id ) as accessRestriction
	from detail_meta as dm
	left join detail_meta_rel as dmr on dmr.id = dm.value	
	order by property; 
END
$func$
LANGUAGE 'plpgsql';

/*
* root view all metadata 
*/
CREATE OR REPLACE FUNCTION gui.root_view_func()
    RETURNS table (id bigint, property text, type text, value text, acdhid  text)
AS $func$
BEGIN
  /* get root ids */
  DROP TABLE IF EXISTS  rootids;
  CREATE TEMP TABLE rootids AS (
	select DISTINCT(r.id) as rootid
	from metadata as m
	left join relations as r on r.id = m.id
	where
 	m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
	and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
	and r.property != 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
	and r.id NOT IN ( 
		SELECT DISTINCT(r.id) from metadata as m left join relations as r on r.id = m.id
		where
 			m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
			and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
			and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
	)
	order by r.id asc
  );

  /* get root raw metadata by the rootids */
  DROP TABLE IF EXISTS root_meta;
  CREATE TEMPORARY TABLE root_meta AS (
  select mv.id, mv.property, mv.type, mv.value
	from identifiers as i
	inner join metadata_view as mv on mv.id = i.id
	inner join rootids as ri on ri.rootid = i.id
	union
	select m.id, m.property, m.type, m.value
	from identifiers as i
	inner join metadata as m on m.id = i.id
	inner join rootids as ri on ri.rootid = i.id
	
  );

  /* get the root relation properties */
  DROP TABLE IF EXISTS root_meta_rel;
	CREATE TEMPORARY TABLE root_meta_rel AS (
	select DISTINCT(m.id), m.value, i.ids as acdhId, rm.id as resId, rm.property, rm.type
	from metadata as m
	left join root_meta as rm on CAST(rm.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
	left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%/id.acdh.oeaw.ac.at/uuid/%' as varchar)
	where rm.type = 'REL'  );

  RETURN QUERY
  Select
	rm.id, rm.property, rm.type, rm.value,  NULL::text AS acdhId
  from root_meta as rm
  where rm.type != 'REL'
  UNION
  SELECT
	rmr.resid as id, rmr.property, rmr.type, rmr.value, rmr.acdhId
  from root_meta_rel as rmr;

END
$func$
LANGUAGE 'plpgsql';

