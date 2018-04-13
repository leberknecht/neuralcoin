import svgwrite
import tflearn


class NetworkImageCreator:
    known_layer_names = []
    input_length = 0
    max_node_count = 0

    x_offset = 20
    y_offset = 40

    x_spacing = 400
    y_spacing = 60
    default_radius = 30

    hidden_layer_count = 0

    max_stroke_width = 3
    max_weight_percentage = 0.0
    min_weight_percentage = 0.0
    max_bias_percentage = 0.0
    min_bias_percentage = 0.0

    connections_drawn = 0

    def __init__(self):
        pass

    def generate_svg(self, model, hidden_layer_prefix='dense', output_layer_name='output'):
        self.input_length = model.inputs[0].shape.dims[1].value
        self.hidden_layer_count = self.set_hidden_layer_count()
        self.known_layer_names = list(map(lambda u: hidden_layer_prefix + str(u), range(self.hidden_layer_count))) + \
            [output_layer_name]
        self.set_min_and_max_values(model)

        image = svgwrite.Drawing()

        self.draw_input_connections(image)

        for layer_index in range(0, len(self.known_layer_names)):
            x = self.x_offset + ((layer_index + 1) * self.x_spacing)
            layer_vars = tflearn.get_layer_variables_by_name(self.known_layer_names[layer_index])
            weights = model.get_weights(layer_vars[0])
            self.draw_layer_connections(image, weights, x)

        for layer_index in range(0, len(self.known_layer_names)):
            layer_vars = tflearn.get_layer_variables_by_name(self.known_layer_names[layer_index])
            self.draw_layer_nodes(image, layer_index, layer_vars, model)

        self.draw_input_nodes(image)
        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' + image.tostring()

    def draw_layer_nodes(self, image, layer_index, layer_vars, model):
        with model.session.as_default():
            weights = model.get_weights(layer_vars[0])
            target_node_count = weights.shape[1]

            for i in range(target_node_count):
                radius = self.default_radius
                if len(layer_vars) > 1:
                    bias_values = tflearn.variables.get_value(layer_vars[1])
                    bias_val = float(bias_values[i])
                    bias_val = float(max(float(bias_val), float(bias_val * -1)))
                    radius = int(float(radius * (
                        bias_val / max((self.max_bias_percentage, self.min_bias_percentage * -1))
                    )))
                x = self.x_offset + ((layer_index + 2) * self.x_spacing)
                y = self.y_offset + (i * self.y_spacing)
                y += (((self.max_node_count - target_node_count) * self.y_spacing) / 2)
                image.add(image.circle(
                    (x, y),
                    radius
                ))

    def draw_layer_connections(self, image, weights, x):
        print("number of weight rows: " + str(len(weights)))
        for weights_row_index in range(len(weights)):
            y = self.y_offset + (weights_row_index * self.y_spacing)
            y += ((self.max_node_count - len(weights)) * self.y_spacing) / 2
            for target_index in range(len(weights[weights_row_index])):
                target_nodes_count = len(weights[weights_row_index])
                target_y = self.y_offset + (((self.max_node_count - target_nodes_count) * self.y_spacing) / 2)
                target_y += (target_index * self.y_spacing)
                weight = weights[weights_row_index][target_index]
                if weight > 0:
                    stroke_color = svgwrite.rgb(10, 10, 16, '%')
                else:
                    stroke_color = svgwrite.rgb(40, 10, 16, '%')
                weight = max(weight, weight * -1)
                stroke_width = self.max_stroke_width * (
                    weight / float(max((self.max_weight_percentage, self.min_weight_percentage * -1)))
                )
                image.add(image.line(
                    (x, y),
                    (x + self.x_spacing, target_y),
                    stroke=stroke_color,
                    stroke_width=int(stroke_width)
                ))
                self.connections_drawn += 1

    def draw_input_connections(self, image):
        for i in range(self.input_length):
            image.add(image.line(
                (self.x_offset, self.y_offset + (i * self.y_spacing)),
                (self.x_offset + self.x_spacing, self.y_offset + (i * self.y_spacing)),
                stroke=svgwrite.rgb(10, 10, 16, '%'),
                stroke_width=self.max_stroke_width
            ))

    def draw_input_nodes(self, image):
        for i in range(self.input_length):
            image.add(image.circle(
                (self.x_offset + self.x_spacing, self.y_offset + (i * self.y_spacing)),
                self.default_radius
            ))

    def set_hidden_layer_count(self):
        x = 0
        while tflearn.get_layer_variables_by_name('dense' + str(x)):
            x += 1
        return x

    def set_min_and_max_values(self, model):
        for layer_name in self.known_layer_names:
            layer_vars = tflearn.get_layer_variables_by_name(layer_name)
            weights = model.get_weights(layer_vars[0])
            tmp_max = float(max(map(max, weights)))
            self.max_weight_percentage = float(max(tmp_max, self.max_weight_percentage))
            tmp_min = float(min(map(max, weights)))
            self.min_weight_percentage = float(max(tmp_min, self.min_weight_percentage))
            self.max_node_count = max(max(map(len, weights)), self.input_length)
            with model.session.as_default():
                if len(layer_vars) > 1:
                    bias_values = tflearn.variables.get_value(layer_vars[1])
                    self.min_bias_percentage = float(min((self.min_bias_percentage, float(min(bias_values)))))
                    self.max_bias_percentage = float(max((self.max_bias_percentage, float(max(bias_values)))))


