<?php
namespace Com\Tqdev\CrudApi\OpenApi;

use Com\Tqdev\CrudApi\Meta\MetaService;

class OpenApiDefinition extends DefaultOpenApiDefinition
{
    private function set(String $path, String $value): void {
        $parts = explode('/',trim($path,'/'));
        $current = &$this->root;
        while (count($parts)>0) {
            $part = array_shift($parts);
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }
        $current = $value;
    }

	public function setPaths(DatabaseDefinition $database):void {
        $result = [];
		foreach ($database->getTables() as $database) {
            $path = sprintf('/data/%s', $table->getName());
			foreach ([ 'get', 'post', 'put', 'patch', 'delete' ] as $method) {
                $this->set("/paths/$path/$method/description", "$method operation");
			}
		}
	}

	private function fillParametersWithPrimaryKey(String $method, TableDefinition $table): void {
		if (table.getPk() != null) {
            $pathWithId = sprintf('/data/%s/{%s}', $table->getName(), $table->getPk()->getName());
			$this->set("/paths/$pathWithId/$method/responses/200/description", "$method operation");
			fillResponseParameters(table, operation);
		}
	}

	private function fillResponseParameters(TableDefinition table, ObjectNode operation):void {
        $this->set("/paths/$path/$method/responses/200", method + " operation");
		ArrayNode parameters = operation.putArray("parameters");

		// TODO: replace repeated objects with references
		ObjectNode node = JsonNodeFactory.instance.objectNode();
		node.put("name", table.getPk().getName());
		node.put("in", "path");
		node.put("required", true);
		ObjectNode schema = node.with("schema");
		schema.put("type", convertTypeToJSONType(table.getPk().getName()));

		parameters.add(node);
	}

	private void fillContentResponse(TableDefinition table, ObjectNode okResponse) {
		ObjectNode content = okResponse.with("content");
		ObjectNode json = content.with("application/json");
		ObjectNode schema = json.with("schema");
		schema.put("type", "object");
		ObjectNode properties = schema.with("properties");

		Collection<ColumnDefinition> columns = table.getColumns();
		for (ColumnDefinition columnDefinition : columns) {
			ObjectNode col = properties.with(columnDefinition.getName());
			col.put("type", convertTypeToJSONType(columnDefinition.getType()));
		}

		ObjectNode example = schema.with("example");
		for (ColumnDefinition columnDefinition : columns) {
			String decodeType = convertTypeToJSONType(columnDefinition.getType());
			example.put(columnDefinition.getName(), getExampleForJSONType(decodeType));
		}
	}

	private String convertTypeToJSONType(String type) {
		String jsonType = "string";
		switch (type.split(" ")[0]) {
		case "varchar":
		case "char":
		case "longvarchar":
		case "clob":
			jsonType = "string";
			break;
		case "nvarchar":
		case "nchar":
		case "longnvarchar":
		case "nclob":
			jsonType = "string";
			break;
		case "boolean":
		case "bit":
			jsonType = "boolean";
		case "tinyint":
		case "smallint":
		case "integer":
		case "bigint":
			jsonType = "integer";
			break;
		case "double":
		case "float":
		case "real":
			jsonType = "float";
			break;
		case "numeric":
		case "decimal":
			jsonType = "float";
			break;
		case "date":
		case "time":
		case "timestamp":
			jsonType = "string";
			break;
		case "binary":
		case "varbinary":
		case "longvarbinary":
		case "blob":
			jsonType = "string";
			break;
		default:
			jsonType = "?";
		}
		return jsonType;
	}

	private String getExampleForJSONType(String jsonType) {
		String example;
		switch (jsonType) {
		case "string":
			example = "some text";
			break;
		case "integer":
			example = "1";
			break;
		case "boolean":
			example = "true";
			break;
		case "array":
			example = "[]";
		default:
			example = "?";
		}
		return example;
	}

}
