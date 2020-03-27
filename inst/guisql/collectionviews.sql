--DROP FUNCTION collection_views_func(text,text)
--select * from collection_views_func('51347');
CREATE OR REPLACE FUNCTION gui.collection_views_func(_pid text, _lang text DEFAULT 'en' )
    RETURNS table (mainid bigint, parentid bigint, title text, accesres text, license text, binarysize text, filename text, locationpath text, depth integer)
AS $func$
BEGIN

DROP TABLE IF EXISTS accessres;
	CREATE TEMP TABLE accessres AS (
	select 
	distinct(r.target_id) as accessid , mv.value,
	mv.lang
	from relations as r
	left join metadata_view as mv on mv.id = r.target_id
	where r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
	and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
	and mv.lang = _lang
);

DROP TABLE IF EXISTS colldata;
	CREATE TEMPORARY TABLE colldata(mainid bigint, parentid bigint, title text, accesres text, license text, binarysize text, filename text, locationpath text, depth integer);
	INSERT INTO colldata( 
		WITH RECURSIVE subordinates AS (
		   SELECT
			  mv.id as mainid,
			CAST(mv.value as bigint) as parentid, 
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFilename' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLocationPath' and id = mv.id limit 1),
			   1 as depthval
		   FROM
			metadata_view as mv
		   WHERE
			  mv.value = _pid
			   and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
		   UNION
			  SELECT
				 mv2.id,
			CAST(mv2.value as bigint) as m2val, 
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFilename' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLocationPath' and id = mv2.id limit 1),
				depthval + 1 
			  FROM
				 metadata_view as mv2
			  INNER JOIN subordinates s ON s.mainid = CAST(mv2.value as bigint) and  mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
		) select * from subordinates
	);

RETURN QUERY
   select mv.mainid, mv.parentid, mv.title, ar.value, mv.license, mv.binarysize, mv.filename, mv.locationpath, mv.depth 
   from 
   colldata as mv
   left join accessres as ar on CAST(mv.accesres as integer) = ar.accessid;
END
$func$
LANGUAGE 'plpgsql';


select * from collection_views_func('51347');
