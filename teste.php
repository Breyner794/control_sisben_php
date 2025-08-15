<?php if ($mostrar_vista_previa): ?>

            <div class="mb-8 p-6 bg-orange-50 rounded-xl border border-orange-200">
                <h2 class="text-2xl font-semibold text-orange-700 mb-4">Vista Previa - Confirmar Procesamiento</h2>
                <form action="" method="post">
                    <?php if (!empty($_SESSION['excel_data']['nuevos_registros'])): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-green-700 mb-3">Registros Nuevos (<?php echo count($_SESSION['excel_data']['nuevos_registros']); ?>)</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-green-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Tipo Doc</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">Documento</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">P. Apellido</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">S. Apellido</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">P. Nombre</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-700">S. Nombre</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach (array_slice($_SESSION['excel_data']['nuevos_registros'], 0, 10) as $registro): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($registro['tipoDoc']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($registro['documento']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($registro['p_apellido']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($registro['s_apellido']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($registro['p_nombre']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($registro['s_nombre']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($_SESSION['excel_data']['nuevos_registros']) > 10): ?>
                                    <tr>
                                        <td colspan="6" class="px-4 py-2 text-center text-gray-500">
                                            ... y <?php echo count($_SESSION['excel_data']['nuevos_registros']) - 10; ?> más
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['excel_data']['duplicados'])): ?>
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-red-700 mb-3">Duplicados con Cambios (<?php echo count($_SESSION['excel_data']['duplicados']); ?>)</h3>
                            <?php foreach ($_SESSION['excel_data']['duplicados'] as $duplicado): ?>
                            <div class="mb-4 p-4 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-red-800">Documento: <?php echo htmlspecialchars($duplicado['registro']['documento']); ?></h4>
                                    <div class="space-x-2">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="duplicados[<?php echo $duplicado['registro']['documento']; ?>]" value="mantener" checked class="mr-1">
                                            <span class="text-sm">Mantener existente</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="duplicados[<?php echo $duplicado['registro']['documento']; ?>]" value="actualizar" class="mr-1">
                                            <span class="text-sm text-red-600">Actualizar con nuevo</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <h5 class="font-medium text-gray-700 mb-1">Datos Existentes:</h5>
                                        <p><span class="font-medium">Tipo:</span> <?php echo htmlspecialchars($duplicado['existente']['TipoDoc']); ?></p>
                                        <p><span class="font-medium">Nombre:</span> <?php echo htmlspecialchars($duplicado['existente']['P_Nombre'] . ' ' . $duplicado['existente']['S_Nombre']); ?></p>
                                        <p><span class="font-medium">Apellidos:</span> <?php echo htmlspecialchars($duplicado['existente']['P_Apellido'] . ' ' . $duplicado['existente']['S_Apellido']); ?></p>
                                    </div>
                                    <div>
                                        <h5 class="font-medium text-gray-700 mb-1">Datos Nuevos (del Excel):</h5>
                                        <p><span class="font-medium">Tipo:</span> <?php echo htmlspecialchars($duplicado['registro']['tipoDoc']); ?></p>
                                        <p><span class="font-medium">Nombre:</span> <?php echo htmlspecialchars($duplicado['registro']['p_nombre'] . ' ' . $duplicado['registro']['s_nombre']); ?></p>
                                        <p><span class="font-medium">Apellidos:</span> <?php echo htmlspecialchars($duplicado['registro']['p_apellido'] . ' ' . $duplicado['registro']['s_apellido']); ?></p>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-red-600">
                                    <span class="font-medium">Cambios detectados en:</span> <?php echo implode(', ', $duplicado['cambios']); ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-center space-x-4">
                        <button type="submit" name="confirmarExcel" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition duration-300">
                            Confirmar y Procesar
                        </button>
                        <button type="submit" name="cancelarExcel" class="px-6 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 transition duration-300">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>