--DROP FUNCTION breadcrumb_view_func(text,text)
--select * from collection_views_func('51347');
CREATE OR REPLACE FUNCTION gui.breadcrumb_view_func(_pid text, _lang text DEFAULT 'en' )
    RETURNS table (mainid bigint, parentid bigint, parentTitle text, depth integer)
AS $func$
BEGIN

DROP TABLE IF EXISTS breadcrumbdata;
	CREATE TEMPORARY TABLE breadcrumbdata(mainid bigint, parentid bigint, depth integer);
	INSERT INTO breadcrumbdata( 
		WITH RECURSIVE subordinates AS (
		   SELECT
			  mv.id as mainid,
			CAST(mv.value as bigint) as parentid,
			   1 as depthval
		   FROM
			metadata_view as mv
		   WHERE
			  mv.id = CAST(_pid as bigint)
			   and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
		   UNION
			  SELECT
				 mv2.id,
			CAST(mv2.value as bigint) as m2val,
				depthval + 1 
			  FROM
				 metadata_view as mv2
			  INNER JOIN subordinates s ON s.parentid = mv2.id and  mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
		) select * from subordinates
	);

RETURN QUERY
   select 
   bd.mainid, bd.parentid, 
	mv.value,
   bd.depth 
   from 
   breadcrumbdata as bd
   left join metadata_view as mv on mv.id = bd.parentid 
   where  mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
END
$func$
LANGUAGE 'plpgsql';


select * from breadcrumb_view_func('607');
