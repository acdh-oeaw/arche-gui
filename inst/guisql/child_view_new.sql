
--DROP FUNCTION child_views_func(text,text,text,text,text,text)
--select * from child_view_func('https://id.acdh.oeaw.ac.at/uuid/57777494-57e5-6f8f-c170-461cecbb44b3', '10', '0', 'value asc', 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle');

CREATE OR REPLACE FUNCTION gui.child_views_func(_parentid text, _limit text, _page text, _orderby text, _orderprop text, _lang text DEFAULT 'en' )
    RETURNS table (childid bigint, property text, value text, order_prop text, order_val text, orderid bigint, lang text)
AS $func$
DECLARE limitint bigint := cast ( _limit as bigint);
DECLARE pageint bigint := cast ( _page as bigint);

BEGIN

RAISE NOTICE USING MESSAGE = _lang;
RAISE NOTICE USING MESSAGE = _parentid;
RAISE NOTICE USING MESSAGE = _orderby;
RAISE NOTICE USING MESSAGE = _orderprop;
RAISE NOTICE USING MESSAGE = _limit;
RAISE NOTICE USING MESSAGE = _page;

	/* get child ids */
	DROP TABLE IF EXISTS child_ids;
	CREATE TEMPORARY TABLE child_ids(childid bigint NOT NULL, prop text NOT NULL, value text NOT NULL);
	INSERT INTO child_ids( 
		select 
			r.id as childid, mv.property, mv.value
		from relations as r
		left join identifiers as i on i.id = r.target_id 
		left join metadata_view as mv on mv.id = r.id
		where r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
		and mv.property = _orderprop
		and i.ids = _parentid
		order by  
		(CASE WHEN _orderby = 'asc' THEN mv.value END) ASC,
         mv.value DESC
		limit limitint
		offset pageint
	); 
	ALTER TABLE child_ids ADD COLUMN id SERIAL PRIMARY KEY;
	
RETURN QUERY
		select 
		CAST(ci.childid as bigint),  mv.property, 
		CASE 
		WHEN mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' 
		THEN  
		(select mv2.value from metadata_view as mv2 where id = CAST(mv.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' LIMIT 1) 
		ELSE 
		mv.value 
		END,
		ci.prop, ci.value, CAST(ci.id as bigint),
		mv.lang
		from child_ids as ci
		left join metadata_view as mv on mv.id = ci.childid
		where 
		mv.property in (
			'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'
		) 
		--and mv.lang = _lang
		Order by ci.id
		;
END
$func$
LANGUAGE 'plpgsql';


select * from child_views_func('https://repo.hephaistos.arz.oeaw.ac.at/6603', '10', '0', 'asc', 'http://fedora.info/definitions/v4/repository#lastModified');